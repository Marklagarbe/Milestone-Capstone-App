<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Alert;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskUpdate;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $projects = Project::visibleTo($user)->with(['group.members', 'milestones'])->orderBy('title')->get();
        $base = Task::whereIn('project_id', $projects->pluck('id'));

        $tasks = (clone $base)->with(['project', 'assignee', 'milestone'])
            ->when($request->project, fn ($q, $v) => $q->where('project_id', $v))
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->when($request->mine, fn ($q) => $q->where('assigned_to', $user->id))
            ->when($request->q, fn ($q, $v) => $q->where('title', 'like', "%{$v}%"))
            ->orderByRaw("CASE status WHEN 'completed' THEN 1 ELSE 0 END")
            ->orderByRaw('due_date IS NULL')->orderBy('due_date')
            ->paginate(10)->withQueryString();

        return view('tasks.index', [
            'tasks' => $tasks,
            'projects' => $projects,
            'stats' => [
                'total' => (clone $base)->count(),
                'pending' => (clone $base)->where('status', 'pending')->count(),
                'in_progress' => (clone $base)->where('status', 'in_progress')->count(),
                'completed' => (clone $base)->where('status', 'completed')->count(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'project_id' => ['required', 'exists:projects,id'],
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:3000'],
            'milestone_id' => ['nullable', 'exists:milestones,id'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'due_date' => ['nullable', 'date'],
            'priority' => ['required', 'in:low,medium,high'],
        ]);

        $project = $this->access(Project::findOrFail($data['project_id']));
        $project->loadMissing('group.members');

        // Members can only create tasks for themselves; the leader and faculty can assign to anyone in the group.
        if (! $project->canManage($user)) {
            $data['assigned_to'] = $user->id;
        }
        if (! empty($data['assigned_to']) && ! $project->group->members->contains('id', $data['assigned_to'])) {
            return back()->withErrors(['assigned_to' => 'The assignee must be a member of the project group.'])->withInput();
        }
        if (! empty($data['milestone_id'])) {
            abort_unless($project->milestones()->whereKey($data['milestone_id'])->exists(), 422);
        }

        $task = Task::create($data + ['created_by' => $user->id, 'status' => 'pending', 'progress' => 0]);
        TaskUpdate::create(['task_id' => $task->id, 'user_id' => $user->id, 'type' => 'created', 'message' => 'Task created', 'progress' => 0, 'to_status' => 'pending']);
        Activity::log($project, 'created the task "'.$task->title.'"', 'task');
        Alert::notify([$task->assigned_to], 'New task assigned', $task->title, route('tasks.show', $task), 'task');

        return back()->with('success', 'Task created.');
    }

    public function show(Task $task)
    {
        $project = $this->access($task->project);
        $user = auth()->user();
        $task->load(['assignee', 'creator', 'milestone', 'updates.user']);

        return view('tasks.show', [
            'task' => $task,
            'project' => $project->load('group.members'),
            'canManage' => $project->canManage($user),
            'canUpdate' => $task->assigned_to === $user->id || $project->canManage($user),
        ]);
    }

    public function update(Request $request, Task $task)
    {
        $project = $this->manage($this->access($task->project));
        $project->loadMissing('group.members');
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:3000'],
            'milestone_id' => ['nullable', 'exists:milestones,id'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'due_date' => ['nullable', 'date'],
            'priority' => ['required', 'in:low,medium,high'],
        ]);

        if (! empty($data['assigned_to']) && ! $project->group->members->contains('id', $data['assigned_to'])) {
            return back()->withErrors(['assigned_to' => 'The assignee must be a member of the project group.']);
        }

        $reassigned = (int) $data['assigned_to'] !== (int) $task->assigned_to;
        $task->update($data);
        TaskUpdate::create(['task_id' => $task->id, 'user_id' => $request->user()->id, 'type' => 'comment', 'message' => 'Task details were edited']);

        if ($reassigned) {
            Alert::notify([$task->assigned_to], 'Task assigned to you', $task->title, route('tasks.show', $task), 'task');
        }

        return back()->with('success', 'Task updated.');
    }

    public function destroy(Task $task)
    {
        $project = $this->manage($this->access($task->project));
        $task->delete();
        Activity::log($project, 'deleted the task "'.$task->title.'"', 'task');

        return redirect()->route('tasks.index')->with('success', 'Task deleted.');
    }

    /** Post a progress update; the status follows the percentage. */
    public function progress(Request $request, Task $task)
    {
        $project = $this->access($task->project);
        $user = $request->user();
        abort_unless($task->assigned_to === $user->id || $project->canManage($user), 403, 'Only the assignee or group leader can update progress.');

        $data = $request->validate([
            'progress' => ['required', 'integer', 'min:0', 'max:100'],
            'message' => ['nullable', 'string', 'max:1000'],
        ]);

        $old = $task->status;
        $new = $data['progress'] >= 100 ? 'completed' : ($data['progress'] > 0 ? 'in_progress' : 'pending');

        $task->update([
            'progress' => $data['progress'],
            'status' => $new,
            'completed_at' => $new === 'completed' ? now() : null,
        ]);

        TaskUpdate::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'type' => $old !== $new ? 'status' : 'progress',
            'message' => $data['message'],
            'progress' => $data['progress'],
            'from_status' => $old,
            'to_status' => $new,
        ]);

        Activity::log($project, $new === 'completed'
            ? 'completed the task "'.$task->title.'"'
            : 'updated "'.$task->title.'" to '.$data['progress'].'%', 'task');

        if ($new === 'completed') {
            $group = $project->group;
            Alert::notify(array_merge([$group->instructor, $group->adviser], $group->members->filter(fn ($m) => $m->pivot->role === 'leader')->all()),
                'Task completed', $task->title, route('tasks.show', $task), 'task');
        }

        return back()->with('success', 'Progress updated.');
    }
}
