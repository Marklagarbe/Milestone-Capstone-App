@extends('layouts.app')
@section('title', $task->title)
@section('content')
<div class="page-head">
    <div>
        <div class="actions" style="margin-bottom:8px"><x-pill :status="$task->status" /><x-pill :status="$task->priority" /></div>
        <h1>{{ $task->title }}</h1>
        <p><a href="{{ route('projects.show', $project) }}">{{ $project->title }}</a>{{ $task->milestone ? ', '.$task->milestone->title : '' }}</p>
    </div>
    <div class="actions">
        <a class="btn" href="{{ route('tasks.index', ['project' => $project->id]) }}">Back to tasks</a>
        @if ($canManage)
            <button class="btn" type="button" data-open="editTask"><x-icon name="edit" size="18" /> Edit</button>
            <form method="POST" action="{{ route('tasks.destroy', $task) }}" class="inline" onsubmit="return confirm('Delete this task?')">
                @csrf @method('DELETE')<button class="btn btn-danger" type="submit"><x-icon name="trash" size="18" /></button>
            </form>
        @endif
    </div>
</div>

<div class="grid split">
    <div class="stack">
        <div class="card">
            <h2 style="margin-bottom:12px">Details</h2>
            <p style="white-space:pre-line">{{ $task->description ?: 'No details were added.' }}</p>
            <hr class="divider">
            <dl class="kv">
                <dt>Assigned to</dt><dd>@if ($task->assignee)<span style="display:inline-flex;align-items:center;gap:8px"><x-avatar :user="$task->assignee" :size="26" /> {{ $task->assignee->name }}</span>@else Unassigned @endif</dd>
                <dt>Due date</dt><dd class="{{ $task->isOverdue() ? 'tag-late' : '' }}">{{ $task->due_date?->format('M d, Y') ?? 'No due date' }}{{ $task->isOverdue() ? ' (overdue)' : '' }}</dd>
                <dt>Created by</dt><dd>{{ $task->creator?->name ?? 'Unknown' }}, {{ $task->created_at->format('M d, Y') }}</dd>
                <dt>Progress</dt><dd style="max-width:260px"><x-bar :value="$task->progress" :green="$task->status === 'completed'" /></dd>
            </dl>
        </div>

        @if ($canUpdate)
        <div class="card">
            <h2 style="margin-bottom:12px">Post a progress update</h2>
            <form method="POST" action="{{ route('tasks.progress', $task) }}">
                @csrf
                <div class="field">
                    <label for="progress">Progress: <output id="pv">{{ $task->progress }}%</output></label>
                    <input id="progress" type="range" name="progress" min="0" max="100" step="5" value="{{ $task->progress }}" oninput="document.getElementById('pv').textContent = this.value + '%'">
                    <span class="small muted">0% is pending, 5 to 95% is in progress, 100% marks the task completed.</span>
                </div>
                <div class="field"><label for="message">What did you do?</label><textarea id="message" name="message" style="min-height:70px" placeholder="Short note for your group"></textarea></div>
                <button class="btn btn-primary" type="submit">Save update</button>
            </form>
        </div>
        @endif
    </div>

    <div class="card">
        <h2 style="margin-bottom:14px">Activity history</h2>
        <div class="hist">
            @foreach ($task->updates as $u)
                <div>
                    <b>{{ $u->user?->name ?? 'Someone' }}</b>
                    <span class="small muted">{{ $u->created_at->format('M d, g:i A') }}</span>
                    @if ($u->type === 'created')
                        <div class="small">Created the task</div>
                    @else
                        @if (! is_null($u->progress))<div class="small">Progress set to {{ $u->progress }}%{{ $u->from_status && $u->to_status && $u->from_status !== $u->to_status ? ', status '.str_replace('_', ' ', $u->from_status).' to '.str_replace('_', ' ', $u->to_status) : '' }}</div>@endif
                        @if ($u->message)<div class="small muted">{{ $u->message }}</div>@endif
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>

@if ($canManage)
<dialog id="editTask">
    <form method="POST" action="{{ route('tasks.update', $task) }}">
        @csrf @method('PUT')
        <input type="hidden" name="_dialog" value="editTask">
        <div class="dlg-head"><h2>Edit task</h2><button class="btn btn-icon" type="button" data-close aria-label="Close"><x-icon name="x" size="18" /></button></div>
        <div class="dlg-body">
            <div class="field"><label>Title</label><input type="text" name="title" value="{{ old('title', $task->title) }}" required></div>
            <div class="row2">
                <div class="field"><label>Assign to</label>
                    <select name="assigned_to"><option value="">Unassigned</option>@foreach ($project->group->members as $m)<option value="{{ $m->id }}" @selected($task->assigned_to == $m->id)>{{ $m->name }}</option>@endforeach</select></div>
                <div class="field"><label>Due date</label><input type="date" name="due_date" value="{{ old('due_date', optional($task->due_date)->format('Y-m-d')) }}"></div>
            </div>
            <div class="row2">
                <div class="field"><label>Milestone</label>
                    <select name="milestone_id"><option value="">None</option>@foreach ($project->milestones as $m)<option value="{{ $m->id }}" @selected($task->milestone_id == $m->id)>{{ str($m->title)->limit(40) }}</option>@endforeach</select></div>
                <div class="field"><label>Priority</label>
                    <select name="priority">@foreach (['low' => 'Low', 'medium' => 'Medium', 'high' => 'High'] as $k => $v)<option value="{{ $k }}" @selected($task->priority === $k)>{{ $v }}</option>@endforeach</select></div>
            </div>
            <div class="field"><label>Details</label><textarea name="description">{{ old('description', $task->description) }}</textarea></div>
        </div>
        <div class="dlg-foot"><button class="btn" type="button" data-close>Cancel</button><button class="btn btn-primary" type="submit">Save changes</button></div>
    </form>
</dialog>
@endif
@endsection
