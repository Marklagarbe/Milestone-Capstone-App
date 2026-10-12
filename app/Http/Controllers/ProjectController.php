<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Alert;
use App\Models\Group;
use App\Models\Project;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $all = Project::visibleTo($request->user())->get();

        $projects = Project::visibleTo($request->user())->with('group.members')
            ->when($request->q, fn ($q, $t) => $q->where('title', 'like', "%{$t}%"))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->latest()->get();

        return view('projects.index', [
            'projects' => $projects,
            'counts' => [
                'total' => $all->count(),
                'in_progress' => $all->whereIn('status', ['proposal', 'in_progress'])->count(),
                'under_review' => $all->where('status', 'under_review')->count(),
                'completed' => $all->where('status', 'completed')->count(),
            ],
        ]);
    }

    public function create(Request $request)
    {
        return view('projects.create', ['groups' => $this->manageableGroups($request), 'project' => new Project(['type' => 'thesis'])]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'group_id' => ['required', 'exists:project_groups,id'],
            'title' => ['required', 'string', 'max:200'],
            'type' => ['required', 'in:thesis,capstone'],
            'description' => ['nullable', 'string', 'max:3000'],
            'start_date' => ['nullable', 'date'],
            'target_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $group = Group::findOrFail($data['group_id']);
        abort_unless($group->canManage($request->user()), 403, 'Only the group leader or faculty can create a project for this group.');

        $project = Project::create($data + ['created_by' => $request->user()->id, 'status' => 'proposal']);

        if ($request->boolean('use_template', true)) {
            $project->seedTemplate();
        }

        Activity::log($project, 'created the project "'.$project->title.'"', 'project');
        Alert::notify($project->participants(), 'New project created', $project->title, route('projects.show', $project), 'info');

        return redirect()->route('projects.show', $project)->with('success', 'Project created. Phases and milestones are ready.');
    }

    public function show(Project $project)
    {
        $this->access($project);
        $project->load(['group.members', 'group.instructor', 'group.adviser', 'phases.milestones.feedback.author']);

        $unphased = $project->milestones()->whereNull('phase_id')->get();
        $user = auth()->user();

        return view('projects.show', [
            'project' => $project,
            'unphased' => $unphased,
            'canManage' => $project->canManage($user),
            'isFaculty' => $project->isFacultyFor($user),
            'activities' => $project->activities()->with('user')->limit(8)->get(),
            'feedback' => $project->feedback()->with(['author', 'milestone', 'document'])->limit(6)->get(),
            'stats' => [
                'tasks_done' => $project->tasks()->where('status', 'completed')->count(),
                'tasks_total' => $project->tasks()->count(),
                'approved' => $project->milestones()->where('status', 'approved')->count(),
                'milestones' => $project->milestones()->count(),
                'files' => $project->documents()->count(),
            ],
        ]);
    }

    public function edit(Project $project)
    {
        $this->manage($this->access($project));

        return view('projects.edit', ['project' => $project, 'groups' => $this->manageableGroups(request())]);
    }

    public function update(Request $request, Project $project)
    {
        $this->manage($this->access($project));
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'type' => ['required', 'in:thesis,capstone'],
            'description' => ['nullable', 'string', 'max:3000'],
            'start_date' => ['nullable', 'date'],
            'target_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);
        $project->update($data);
        Activity::log($project, 'updated the project details', 'project');

        return redirect()->route('projects.show', $project)->with('success', 'Project updated.');
    }

    public function destroy(Project $project)
    {
        $this->manage($this->access($project));
        $project->delete();

        return redirect()->route('projects.index')->with('success', 'Project deleted.');
    }

    public function timeline(Request $request)
    {
        $projects = Project::visibleTo($request->user())->orderBy('title')->get();
        $selected = $projects->firstWhere('id', (int) $request->project) ?? $projects->first();

        $milestones = $selected
            ? $selected->milestones()->with('phase')->orderByRaw('due_date IS NULL')->orderBy('due_date')->orderBy('position')->get()
            : collect();

        return view('projects.timeline', compact('projects', 'selected', 'milestones'));
    }

    private function manageableGroups(Request $request)
    {
        return Group::visibleTo($request->user())->orderBy('name')->get()
            ->filter(fn ($g) => $g->canManage($request->user()))->values();
    }
}
