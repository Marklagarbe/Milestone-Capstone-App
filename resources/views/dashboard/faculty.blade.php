@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
@php
    $me = auth()->user();
    $hour = now()->hour;
    $greet = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
    $who = ($me->role === 'instructor' ? 'Prof. ' : 'Adviser ').$me->last_name;
    $sorted = $rows->sortByDesc('overdue')->values();
@endphp

<section class="hero">
    <div>
        <p class="hero-date">{{ now()->format('l, F j, Y') }}</p>
        <h1>{{ $greet }}, {{ $who }}!</h1>
        <p class="hero-sub">
            @if ($pendingReviews > 0)
                <b>{{ $pendingReviews }}</b> {{ str('item')->plural($pendingReviews) }} {{ $pendingReviews === 1 ? 'is' : 'are' }} waiting for your review.
            @else
                Nothing is waiting for review right now.
            @endif
            @if ($overdue > 0) {{ $overdue }} overdue {{ str('item')->plural($overdue) }} across your groups. @endif
        </p>
        <div class="hero-chips">
            <a class="hero-chip" href="{{ route('groups.index') }}"><x-icon name="users" size="16" /> {{ $groupCount }} {{ str('group')->plural($groupCount) }}</a>
            <a class="hero-chip" href="{{ route('projects.index') }}"><x-icon name="layers" size="16" /> {{ $projects->count() }} {{ str('project')->plural($projects->count()) }}</a>
            @if ($overdue > 0)<span class="hero-chip warn"><x-icon name="alert" size="16" /> {{ $overdue }} overdue</span>@endif
        </div>
        <div class="actions">
            <a class="btn btn-light" href="{{ route('feedback.index') }}"><x-icon name="message" size="18" /> Feedback</a>
            <a class="btn btn-glass" href="{{ route('reports.index') }}"><x-icon name="bar-chart" size="18" /> Reports</a>
            <a class="btn btn-glass" href="{{ route('groups.create') }}"><x-icon name="plus" size="18" /> New group</a>
        </div>
    </div>
    <div class="ring-wrap"><div class="ring" style="--p:{{ $overall }}"><b>{{ $overall }}%</b></div>Average progress</div>
</section>

<div class="grid g4" style="margin-bottom:18px">
    <div class="card stat"><div class="ico ico-blue"><x-icon name="users" size="24" /></div><div><div class="lbl">Groups</div><div class="num c-blue">{{ $groupCount }}</div></div></div>
    <div class="card stat"><div class="ico ico-yellow"><x-icon name="layers" size="24" /></div><div><div class="lbl">Projects</div><div class="num c-yellow">{{ $projects->count() }}</div></div></div>
    <div class="card stat"><div class="ico ico-green"><x-icon name="inbox" size="24" /></div><div><div class="lbl">Waiting for review</div><div class="num c-green">{{ $pendingReviews }}</div></div></div>
    <div class="card stat"><div class="ico ico-red"><x-icon name="alert" size="24" /></div><div><div class="lbl">Overdue items</div><div class="num c-red">{{ $overdue }}</div></div></div>
</div>

<div class="grid split">
    <div class="stack">
        <div class="card">
            <div class="card-head"><h2>Needs your attention</h2></div>
            @if ($toReview->isEmpty() && $docsToReview->isEmpty())
                <div class="empty" style="padding:22px"><x-icon name="check" size="32" /><b>All caught up</b>New submissions will appear here.</div>
            @else
                @if ($toReview->isNotEmpty())
                    <div class="queue-title"><x-icon name="flag" size="16" /> Milestones</div>
                    <div class="rows">
                        @foreach ($toReview as $m)
                            <a href="{{ route('projects.show', $m->project) }}#milestone-{{ $m->id }}" style="color:inherit">
                                <span class="ico ico-yellow"><x-icon name="inbox" size="20" /></span>
                                <span class="grow"><b>{{ $m->title }}</b><span class="muted small">{{ $m->project->title }}, submitted {{ $m->submitted_at?->diffForHumans() }}</span></span>
                                <span class="btn btn-sm btn-primary">Review</span>
                            </a>
                        @endforeach
                    </div>
                @endif
                @if ($docsToReview->isNotEmpty())
                    <div class="queue-title"><x-icon name="file" size="16" /> Files</div>
                    <div class="rows">
                        @foreach ($docsToReview as $d)
                            <a href="{{ route('documents.show', $d) }}" style="color:inherit">
                                <span class="ico ico-blue"><x-icon name="file" size="20" /></span>
                                <span class="grow"><b>{{ $d->title }}</b><span class="muted small">{{ $d->project->title }}, by {{ $d->uploader?->first_name }}</span></span>
                                <span class="btn btn-sm">Open</span>
                            </a>
                        @endforeach
                    </div>
                @endif
            @endif
        </div>

        <div class="card flush">
            <div class="card-head"><h2>Groups at a glance</h2><a class="small" href="{{ route('projects.index') }}">All projects</a></div>
            @if ($sorted->isEmpty())
                <div class="empty"><x-icon name="layers" size="32" /><b>No projects assigned to you yet</b></div>
            @else
                <div class="table-wrap"><table class="t">
                    <thead><tr><th>Project</th><th>Status</th><th style="width:150px">Progress</th><th>Next milestone</th><th>Health</th></tr></thead>
                    <tbody>
                    @foreach ($sorted as $r)
                        <tr>
                            <td><a href="{{ route('projects.show', $r->project) }}" style="color:var(--ink)"><b>{{ $r->project->title }}</b></a><div class="small muted">{{ $r->project->group->name }}</div></td>
                            <td><x-pill :status="$r->project->status" /></td>
                            <td><x-bar :value="$r->progress" /></td>
                            <td class="small">@if ($r->next){{ str($r->next->title)->limit(26) }}@if ($r->next->due_date)<div><x-due :date="$r->next->due_date" /></div>@endif @else <span class="muted">All approved</span>@endif</td>
                            <td>@if ($r->overdue > 0)<span class="pill pill-red">{{ $r->overdue }} overdue</span>@else<span class="pill pill-green">On track</span>@endif</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table></div>
            @endif
        </div>
    </div>

    <div class="stack">
        <div class="card">
            <div class="card-head"><h2>Project progress</h2></div>
            <x-donut :value="$overall" :segments="$segments" :size="150" />
        </div>
        <div class="card">
            <div class="card-head"><h2>Upcoming deadlines</h2><a class="small" href="{{ route('timeline') }}">Timeline</a></div>
            @forelse ($upcoming as $m)
                @if ($loop->first)<div class="rows">@endif
                <a href="{{ route('projects.show', $m->project) }}" style="color:inherit">
                    <span class="grow"><b>{{ str($m->title)->limit(28) }}</b><span class="muted small">{{ $m->project->title }}</span></span>
                    <x-due :date="$m->due_date" />
                </a>
                @if ($loop->last)</div>@endif
            @empty
                <p class="muted">No upcoming deadlines.</p>
            @endforelse
        </div>
        <div class="card">
            <div class="card-head"><h2>Recent activities</h2></div>
            @forelse ($activities as $a)
                @if ($loop->first)<div class="rows">@endif
                <div>
                    <x-avatar :user="$a->user" :size="34" />
                    <span class="grow"><span><b style="display:inline">{{ $a->user?->first_name }}</b> {{ $a->description }}</span><span class="small muted">{{ $a->project?->title }}, {{ $a->created_at->diffForHumans() }}</span></span>
                </div>
                @if ($loop->last)</div>@endif
            @empty
                <p class="muted">No activity yet.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
