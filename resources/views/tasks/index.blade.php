@extends('layouts.app')
@section('title', 'Tasks')
@section('content')
<div class="page-head">
    <div><h1>Tasks</h1><p>Assign work, set deadlines and follow each person's progress.</p></div>
    <div class="actions">
        @if ($projects->isNotEmpty())
            <button class="btn btn-primary" type="button" data-open="newTask"><x-icon name="plus" size="18" /> New task</button>
        @endif
    </div>
</div>

<div class="grid g4" style="margin-bottom:18px">
    <div class="card stat"><div class="ico ico-blue"><x-icon name="check-square" size="24" /></div><div><div class="lbl">All tasks</div><div class="num">{{ $stats['total'] }}</div></div></div>
    <div class="card stat"><div class="ico ico-gray"><x-icon name="clock" size="24" /></div><div><div class="lbl">Pending</div><div class="num">{{ $stats['pending'] }}</div></div></div>
    <div class="card stat"><div class="ico ico-yellow"><x-icon name="history" size="24" /></div><div><div class="lbl">In progress</div><div class="num c-yellow">{{ $stats['in_progress'] }}</div></div></div>
    <div class="card stat"><div class="ico ico-green"><x-icon name="check" size="24" /></div><div><div class="lbl">Completed</div><div class="num c-green">{{ $stats['completed'] }}</div></div></div>
</div>

<form class="filters" method="GET">
    <input class="grow" type="search" name="q" placeholder="Search tasks" value="{{ request('q') }}">
    <select name="project" onchange="this.form.submit()" aria-label="Project">
        <option value="">All projects</option>
        @foreach ($projects as $p)<option value="{{ $p->id }}" @selected(request('project') == $p->id)>{{ $p->title }}</option>@endforeach
    </select>
    <select name="status" onchange="this.form.submit()" aria-label="Status">
        <option value="">All statuses</option>
        @foreach (['pending' => 'Pending', 'in_progress' => 'In Progress', 'completed' => 'Completed'] as $k => $v)<option value="{{ $k }}" @selected(request('status') === $k)>{{ $v }}</option>@endforeach
    </select>
    <label class="check"><input type="checkbox" name="mine" value="1" @checked(request('mine')) onchange="this.form.submit()"> Only my tasks</label>
    <button class="btn" type="submit">Search</button>
</form>

<div class="card flush">
    @if ($tasks->isEmpty())
        <div class="empty"><x-icon name="check-square" size="36" /><b>No tasks found</b>Create a task to assign work to your group.</div>
    @else
        <div class="table-wrap">
            <table class="t">
                <thead><tr><th>Task</th><th>Project</th><th>Assigned to</th><th>Due</th><th>Priority</th><th>Status</th><th style="width:150px">Progress</th></tr></thead>
                <tbody>
                @foreach ($tasks as $t)
                    <tr>
                        <td>
                            <a href="{{ route('tasks.show', $t) }}" style="color:var(--ink)"><b>{{ $t->title }}</b></a>
                            @if ($t->milestone)<div class="small muted">{{ str($t->milestone->title)->limit(40) }}</div>@endif
                        </td>
                        <td class="small">{{ $t->project->title }}</td>
                        <td>@if ($t->assignee)<span style="display:flex;align-items:center;gap:8px"><x-avatar :user="$t->assignee" :size="28" /> {{ $t->assignee->first_name }}</span>@else<span class="muted">Unassigned</span>@endif</td>
                        <td class="nowrap {{ $t->isOverdue() ? 'tag-late' : '' }}">{{ $t->due_date?->format('M d, Y') ?? 'None' }}</td>
                        <td><x-pill :status="$t->priority" /></td>
                        <td><x-pill :status="$t->status" /></td>
                        <td><x-bar :value="$t->progress" :green="$t->status === 'completed'" /></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
{{ $tasks->links() }}

@if ($projects->isNotEmpty())
<dialog id="newTask">
    <form method="POST" action="{{ route('tasks.store') }}">
        @csrf
        <input type="hidden" name="_dialog" value="newTask">
        <div class="dlg-head"><h2>New task</h2><button class="btn btn-icon" type="button" data-close aria-label="Close"><x-icon name="x" size="18" /></button></div>
        <div class="dlg-body">
            <div class="field"><label for="t_project">Project</label>
                <select id="t_project" name="project_id" required>
                    <option value="">Choose a project</option>
                    @foreach ($projects as $p)<option value="{{ $p->id }}" @selected(old('project_id', request('project')) == $p->id)>{{ $p->title }}</option>@endforeach
                </select></div>
            <div class="field"><label for="t_title">Task title</label><input id="t_title" type="text" name="title" value="{{ old('title') }}" required></div>
            <div class="row2">
                <div class="field"><label for="t_assignee">Assign to</label>
                    <select id="t_assignee" name="assigned_to"><option value="">Unassigned</option>
                        @foreach ($projects as $p)@foreach ($p->group->members as $m)<option data-project="{{ $p->id }}" value="{{ $m->id }}">{{ $m->name }}</option>@endforeach @endforeach
                    </select></div>
                <div class="field"><label for="t_due">Due date</label><input id="t_due" type="date" name="due_date" value="{{ old('due_date') }}"></div>
            </div>
            <div class="row2">
                <div class="field"><label for="t_milestone">Milestone</label>
                    <select id="t_milestone" name="milestone_id"><option value="">None</option>
                        @foreach ($projects as $p)@foreach ($p->milestones as $m)<option data-project="{{ $p->id }}" value="{{ $m->id }}">{{ str($m->title)->limit(40) }}</option>@endforeach @endforeach
                    </select></div>
                <div class="field"><label for="t_priority">Priority</label>
                    <select id="t_priority" name="priority"><option value="low">Low</option><option value="medium" selected>Medium</option><option value="high">High</option></select></div>
            </div>
            <div class="field"><label for="t_desc">Details</label><textarea id="t_desc" name="description">{{ old('description') }}</textarea></div>
            <p class="small muted">Group members can only create tasks for themselves. The leader and faculty can assign to anyone in the group.</p>
        </div>
        <div class="dlg-foot"><button class="btn" type="button" data-close>Cancel</button><button class="btn btn-primary" type="submit">Create task</button></div>
    </form>
</dialog>
@push('scripts')
<script>
    (function () {
        var project = document.getElementById('t_project');
        var selects = [document.getElementById('t_assignee'), document.getElementById('t_milestone')];
        function filter() {
            selects.forEach(function (sel) {
                sel.querySelectorAll('option[data-project]').forEach(function (o) {
                    var ok = o.dataset.project === project.value;
                    o.hidden = !ok; o.disabled = !ok;
                });
                if (sel.selectedOptions[0] && sel.selectedOptions[0].disabled) sel.value = '';
            });
        }
        project.addEventListener('change', filter);
        filter();
    })();
</script>
@endpush
@endif
@endsection
