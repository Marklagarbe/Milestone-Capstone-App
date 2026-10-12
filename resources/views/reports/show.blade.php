@extends('layouts.app')
@section('title', 'Report: '.$project->title)
@section('content')
@php
    $ms = $project->phases->flatMap->milestones;
    $segments = [
        ['Approved', $ms->where('status', 'approved')->count(), '#2f7fe0'],
        ['Submitted', $ms->where('status', 'submitted')->count(), '#f7c548'],
        ['In Progress', $ms->whereIn('status', ['in_progress', 'revision'])->count(), '#f2845c'],
        ['Pending', $ms->where('status', 'pending')->count(), '#3cc4a0'],
    ];
@endphp
<div class="page-head">
    <div><h1>Progress Report</h1><p>{{ $project->title }}. Generated {{ now()->format('F d, Y g:i A') }}.</p></div>
    <div class="actions no-print">
        <a class="btn" href="{{ route('reports.index') }}">All reports</a>
        <button class="btn btn-primary" type="button" onclick="window.print()"><x-icon name="print" size="18" /> Print or save as PDF</button>
    </div>
</div>

<div class="grid split" style="margin-bottom:18px">
    <div class="card">
        <h2 style="margin-bottom:14px">Project summary</h2>
        <dl class="kv">
            <dt>Title</dt><dd>{{ $project->title }}</dd>
            <dt>Type</dt><dd>{{ ucfirst($project->type) }}</dd>
            <dt>Status</dt><dd><x-pill :status="$project->status" /></dd>
            <dt>Group</dt><dd>{{ $project->group->name }}</dd>
            <dt>Instructor</dt><dd>{{ $project->group->instructor?->name ?? 'Not assigned' }}</dd>
            <dt>Adviser</dt><dd>{{ $project->group->adviser?->name ?? 'Not assigned' }}</dd>
            <dt>Start date</dt><dd>{{ $project->start_date?->format('M d, Y') ?? 'Not set' }}</dd>
            <dt>Target date</dt><dd>{{ $project->target_date?->format('M d, Y') ?? 'Not set' }}</dd>
            @if ($project->completed_at)<dt>Completed</dt><dd>{{ $project->completed_at->format('M d, Y') }}</dd>@endif
        </dl>
    </div>
    <div class="card"><h2 style="margin-bottom:14px">Milestones</h2><x-donut :value="$project->progress" :segments="$segments" :size="140" /></div>
</div>

<div class="card flush" style="margin-bottom:18px">
    <div class="card-head"><h2>Phases and milestones</h2></div>
    <div class="table-wrap"><table class="t">
        <thead><tr><th>Phase</th><th>Milestone</th><th>Due</th><th>Status</th><th>Approved</th></tr></thead>
        <tbody>
        @foreach ($project->phases as $phase)
            @foreach ($phase->milestones as $m)
                <tr><td>{{ $loop->first ? $phase->name : '' }}</td><td>{{ $m->title }}</td>
                    <td class="nowrap {{ $m->isOverdue() ? 'tag-late' : '' }}">{{ $m->due_date?->format('M d, Y') ?? 'None' }}</td>
                    <td><x-pill :status="$m->status" /></td><td class="small">{{ $m->approved_at?->format('M d, Y') }}</td></tr>
            @endforeach
        @endforeach
        </tbody>
    </table></div>
</div>

<div class="card flush" style="margin-bottom:18px">
    <div class="card-head"><h2>Member contributions</h2></div>
    <div class="table-wrap"><table class="t">
        <thead><tr><th>Member</th><th>Role</th><th>Tasks assigned</th><th>Completed</th><th style="width:160px">Average progress</th><th>Files uploaded</th></tr></thead>
        <tbody>
        @foreach ($members as $r)
            <tr><td><span style="display:flex;align-items:center;gap:8px"><x-avatar :user="$r->user" :size="28" /> {{ $r->user->name }}</span></td>
                <td><x-pill :status="$r->user->pivot->role" /></td><td>{{ $r->assigned }}</td><td>{{ $r->done }}</td><td><x-bar :value="$r->avg" /></td><td>{{ $r->files }}</td></tr>
        @endforeach
        </tbody>
    </table></div>
</div>

<div class="grid g2">
    <div class="card flush">
        <div class="card-head"><h2>Tasks</h2></div>
        <div class="table-wrap"><table class="t">
            <thead><tr><th>Task</th><th>Assignee</th><th>Due</th><th>Status</th></tr></thead>
            <tbody>
            @forelse ($project->tasks as $t)
                <tr><td>{{ $t->title }}</td><td class="small">{{ $t->assignee?->first_name }}</td>
                    <td class="small nowrap {{ $t->isOverdue() ? 'tag-late' : '' }}">{{ $t->due_date?->format('M d') }}</td><td><x-pill :status="$t->status" /></td></tr>
            @empty
                <tr><td colspan="4" class="muted">No tasks.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </div>
    <div class="card flush">
        <div class="card-head"><h2>Files and versions</h2></div>
        <div class="table-wrap"><table class="t">
            <thead><tr><th>File</th><th>Versions</th><th>Status</th></tr></thead>
            <tbody>
            @forelse ($project->documents as $d)
                <tr><td>{{ $d->title }}</td><td>{{ $d->latestVersion?->version ?? 1 }}</td><td><x-pill :status="$d->status" /></td></tr>
            @empty
                <tr><td colspan="3" class="muted">No files.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </div>
</div>

<div class="card" style="margin-top:18px">
    <h2 style="margin-bottom:10px">Feedback log</h2>
    @forelse ($project->feedback as $f)
        <div class="fb {{ $f->decision }}"><b>{{ $f->author?->name }}</b> <x-pill :status="$f->decision" /> <span class="muted small">{{ $f->created_at->format('M d, Y') }}</span><div>{{ $f->body }}</div></div>
    @empty
        <p class="muted">No feedback recorded.</p>
    @endforelse
</div>
@endsection
