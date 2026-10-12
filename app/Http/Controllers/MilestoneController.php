<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Alert;
use App\Models\Feedback;
use App\Models\Milestone;
use App\Models\Phase;
use App\Models\Project;
use Illuminate\Http\Request;

class MilestoneController extends Controller
{
    public function storePhase(Request $request, Project $project)
    {
        $this->manage($this->access($project));
        $data = $request->validate(['name' => ['required', 'string', 'max:150']]);
        $project->phases()->create($data + ['position' => ($project->phases()->max('position') ?? 0) + 1]);
        Activity::log($project, 'added the phase "'.$data['name'].'"', 'milestone');

        return back()->with('success', 'Phase added.');
    }

    public function updatePhase(Request $request, Phase $phase)
    {
        $this->manage($this->access($phase->project));
        $phase->update($request->validate(['name' => ['required', 'string', 'max:150']]));

        return back()->with('success', 'Phase renamed.');
    }

    public function destroyPhase(Phase $phase)
    {
        $project = $this->manage($this->access($phase->project));
        $phase->delete();
        $project->syncStatus();

        return back()->with('success', 'Phase deleted.');
    }

    public function store(Request $request, Project $project)
    {
        $this->manage($this->access($project));
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'phase_id' => ['nullable', 'exists:phases,id'],
            'due_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        if (! empty($data['phase_id'])) {
            abort_unless($project->phases()->whereKey($data['phase_id'])->exists(), 422);
        }

        $project->milestones()->create($data + ['position' => ($project->milestones()->max('position') ?? 0) + 1]);
        $project->syncStatus();
        Activity::log($project, 'added the milestone "'.$data['title'].'"', 'milestone');

        return back()->with('success', 'Milestone added.');
    }

    public function update(Request $request, Milestone $milestone)
    {
        $this->manage($this->access($milestone->project));
        $milestone->update($request->validate([
            'title' => ['required', 'string', 'max:200'],
            'due_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]));

        return back()->with('success', 'Milestone updated.');
    }

    public function destroy(Milestone $milestone)
    {
        $project = $this->manage($this->access($milestone->project));
        $milestone->delete();
        $project->syncStatus();

        return back()->with('success', 'Milestone deleted.');
    }

    /** A group member starts working on a milestone. */
    public function start(Milestone $milestone)
    {
        $project = $this->memberOf($milestone);
        abort_unless(in_array($milestone->status, ['pending', 'revision'], true), 422);

        $milestone->update(['status' => 'in_progress']);
        $project->syncStatus();
        Activity::log($project, 'started "'.$milestone->title.'"', 'milestone');

        return back()->with('success', 'Milestone marked as in progress.');
    }

    /** A group member submits the milestone for instructor review. */
    public function submit(Milestone $milestone)
    {
        $project = $this->memberOf($milestone);
        abort_unless(in_array($milestone->status, ['pending', 'in_progress', 'revision'], true), 422);

        $milestone->update(['status' => 'submitted', 'submitted_at' => now()]);
        $project->syncStatus();
        Activity::log($project, 'submitted "'.$milestone->title.'" for review', 'milestone');

        $group = $project->group;
        Alert::notify([$group->instructor, $group->adviser], 'Milestone ready for review',
            $project->title.': '.$milestone->title, route('projects.show', $project), 'milestone');

        return back()->with('success', 'Submitted for review. Your instructor has been notified.');
    }

    /** Instructor/adviser approves a milestone or requests a revision. */
    public function review(Request $request, Milestone $milestone)
    {
        $project = $this->faculty($this->access($milestone->project));
        $data = $request->validate([
            'decision' => ['required', 'in:approved,revision'],
            'body' => ['nullable', 'string', 'max:2000', 'required_if:decision,revision'],
        ], ['body.required_if' => 'Please explain what needs to be revised.']);

        $approved = $data['decision'] === 'approved';
        $milestone->update([
            'status' => $approved ? 'approved' : 'revision',
            'approved_at' => $approved ? now() : null,
            'approved_by' => $approved ? $request->user()->id : null,
        ]);

        Feedback::create([
            'project_id' => $project->id,
            'milestone_id' => $milestone->id,
            'user_id' => $request->user()->id,
            'body' => $data['body'] ?: 'Approved. Good work.',
            'decision' => $data['decision'],
        ]);

        $project->syncStatus();
        Activity::log($project, ($approved ? 'approved' : 'requested a revision for').' "'.$milestone->title.'"', 'milestone');
        Alert::notify($project->participants(),
            $approved ? 'Milestone approved' : 'Revision requested',
            $project->title.': '.$milestone->title, route('projects.show', $project), 'feedback');

        return back()->with('success', $approved ? 'Milestone approved.' : 'Revision requested.');
    }

    private function memberOf(Milestone $milestone): Project
    {
        $project = $this->access($milestone->project);
        $user = auth()->user();
        abort_unless($project->group->isMember($user) || $user->isCoordinator(), 403, 'Only group members can update this milestone.');

        return $project;
    }
}
