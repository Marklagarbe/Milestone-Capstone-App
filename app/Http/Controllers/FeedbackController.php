<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Alert;
use App\Models\Feedback;
use App\Models\Project;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    public function index(Request $request)
    {
        $projects = Project::visibleTo($request->user())->orderBy('title')->get();
        $ids = $projects->pluck('id');

        $items = Feedback::whereIn('project_id', $ids)->with(['author', 'project', 'milestone', 'document'])
            ->when($request->project, fn ($q, $v) => $q->where('project_id', $v))
            ->when($request->decision, fn ($q, $v) => $q->where('decision', $v))
            ->latest()->paginate(10)->withQueryString();

        return view('feedback.index', [
            'items' => $items,
            'projects' => $projects,
            'counts' => [
                'all' => Feedback::whereIn('project_id', $ids)->count(),
                'approved' => Feedback::whereIn('project_id', $ids)->where('decision', 'approved')->count(),
                'revision' => Feedback::whereIn('project_id', $ids)->where('decision', 'revision')->count(),
            ],
        ]);
    }

    public function store(Request $request, Project $project)
    {
        $this->access($project);
        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
            'milestone_id' => ['nullable', 'exists:milestones,id'],
        ]);

        if (! empty($data['milestone_id'])) {
            abort_unless($project->milestones()->whereKey($data['milestone_id'])->exists(), 422);
        }

        Feedback::create($data + ['project_id' => $project->id, 'user_id' => $request->user()->id, 'decision' => 'comment']);
        Activity::log($project, 'left a comment', 'feedback');
        Alert::notify($project->participants(), 'New comment on '.$project->title,
            str($data['body'])->limit(80), route('projects.show', $project), 'feedback');

        return back()->with('success', 'Comment posted.');
    }
}
