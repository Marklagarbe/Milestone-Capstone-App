@extends('layouts.app')
@section('title', 'Timeline')
@section('content')
<div class="page-head">
    <div><h1>Project Timeline</h1><p>Milestones in the order they are due.</p></div>
    @if ($projects->count() > 1)
        <form method="GET" class="actions"><select name="project" onchange="this.form.submit()" aria-label="Project">
            @foreach ($projects as $p)<option value="{{ $p->id }}" @selected($selected && $selected->id === $p->id)>{{ $p->title }}</option>@endforeach
        </select></form>
    @endif
</div>

@if (! $selected)
    <div class="card empty"><x-icon name="timeline" size="36" /><b>No project to show</b>Create a project to see its timeline.</div>
@else
    <div class="grid split">
        <div class="card">
            <div class="card-head"><h2>{{ $selected->title }}</h2><a class="btn btn-sm" href="{{ route('projects.show', $selected) }}">Open project</a></div>
            @if ($milestones->isEmpty())
                <p class="muted">This project has no milestones yet.</p>
            @else
                <div class="tl">
                    @foreach ($milestones as $m)
                        <div class="node s-{{ $m->status }}">
                            <div class="when {{ $m->isOverdue() ? 'tag-late' : '' }}">{{ $m->due_date ? $m->due_date->format('M d, Y') : 'No due date' }}{{ $m->isOverdue() ? ' (overdue)' : '' }}</div>
                            <b>{{ $m->title }}</b>
                            <div class="small muted">{{ $m->phase?->name ?? 'Other' }}{{ $m->approved_at ? ', approved '.$m->approved_at->format('M d') : '' }}</div>
                            <div style="margin-top:6px"><x-pill :status="$m->status" /></div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
        <div class="card">
            <h2 style="margin-bottom:14px">Overall progress</h2>
            <x-donut :value="$selected->progress" :segments="[
                ['Approved', $milestones->where('status', 'approved')->count(), '#2f7fe0'],
                ['Submitted', $milestones->where('status', 'submitted')->count(), '#f7c548'],
                ['In Progress', $milestones->whereIn('status', ['in_progress', 'revision'])->count(), '#f2845c'],
                ['Pending', $milestones->where('status', 'pending')->count(), '#3cc4a0'],
            ]" :size="140" />
            <hr class="divider">
            <dl class="kv">
                <dt>Started</dt><dd>{{ $selected->start_date?->format('M d, Y') ?? 'Not set' }}</dd>
                <dt>Target</dt><dd>{{ $selected->target_date?->format('M d, Y') ?? 'Not set' }}</dd>
            </dl>
        </div>
    </div>
@endif
@endsection
