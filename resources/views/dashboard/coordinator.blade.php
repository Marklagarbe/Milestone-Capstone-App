@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
@php
    $me = auth()->user();
    $hour = now()->hour;
    $greet = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
    $total = $projects->count();
@endphp

<section class="hero">
    <div>
        <p class="hero-date">{{ now()->format('l, F j, Y') }}</p>
        <h1>{{ $greet }}, {{ $me->first_name }}!</h1>
        <p class="hero-sub">School-wide view of every thesis and capstone project, {{ $studentCount }} {{ str('student')->plural($studentCount) }} and {{ $facultyCount }} faculty {{ str('member')->plural($facultyCount) }}.</p>
        <div class="hero-chips">
            <a class="hero-chip" href="{{ route('projects.index') }}"><x-icon name="layers" size="16" /> {{ $total }} {{ str('project')->plural($total) }}</a>
            <a class="hero-chip" href="{{ route('groups.index') }}"><x-icon name="users" size="16" /> {{ $groupCount }} {{ str('group')->plural($groupCount) }}</a>
            @if ($pendingUserCount > 0)<a class="hero-chip warn" href="{{ route('users.index') }}"><x-icon name="user" size="16" /> {{ $pendingUserCount }} {{ str('account')->plural($pendingUserCount) }} to approve</a>@endif
        </div>
        <div class="actions">
            <a class="btn btn-light" href="{{ route('reports.index') }}"><x-icon name="bar-chart" size="18" /> Reports</a>
            <a class="btn btn-glass" href="{{ route('users.index') }}"><x-icon name="users" size="18" /> Manage users</a>
            <a class="btn btn-glass" href="{{ route('reports.export') }}"><x-icon name="download" size="18" /> Export CSV</a>
        </div>
    </div>
    <div class="ring-wrap"><div class="ring" style="--p:{{ $overall }}"><b>{{ $overall }}%</b></div>Average progress</div>
</section>

<div class="grid g4" style="margin-bottom:18px">
    <div class="card stat"><div class="ico ico-blue"><x-icon name="layers" size="24" /></div><div><div class="lbl">Projects</div><div class="num c-blue">{{ $total }}</div></div></div>
    <div class="card stat"><div class="ico ico-purple"><x-icon name="users" size="24" /></div><div><div class="lbl">Groups</div><div class="num c-purple">{{ $groupCount }}</div></div></div>
    <div class="card stat"><div class="ico ico-yellow"><x-icon name="inbox" size="24" /></div><div><div class="lbl">Waiting for review</div><div class="num c-yellow">{{ $pendingReviews }}</div></div></div>
    <div class="card stat"><div class="ico ico-red"><x-icon name="alert" size="24" /></div><div><div class="lbl">Overdue items</div><div class="num c-red">{{ $overdue }}</div></div></div>
</div>

<div class="grid g2" style="margin-bottom:18px">
    <div class="card">
        <div class="card-head"><h2>Projects by stage</h2><span class="muted small">{{ $total }} total</span></div>
        @foreach ([['proposal', 'Proposal', '#7a5ad8'], ['in_progress', 'In progress', '#2f7fe0'], ['under_review', 'Under review', '#f0a912'], ['completed', 'Completed', '#25b07c']] as [$key, $label, $color])
            @php $n = $statusCounts[$key] ?? 0; $pct = $total ? round($n / $total * 100) : 0; @endphp
            <div class="hbar">
                <div class="hbar-top"><span>{{ $label }}</span><b>{{ $n }}</b></div>
                <div class="bar"><i style="width:{{ $pct }}%;background:{{ $color }}"></i></div>
            </div>
        @endforeach
    </div>
    <div class="card">
        <div class="card-head"><h2>Milestones across all projects</h2></div>
        <x-donut :value="$overall" :segments="$segments" :size="150" />
    </div>
</div>

<div class="grid g2" style="margin-bottom:18px">
    <div class="card flush">
        <div class="card-head"><h2>Faculty workload</h2></div>
        @if ($faculty->isEmpty())
            <div class="empty"><x-icon name="users" size="32" /><b>No approved faculty yet</b></div>
        @else
            <div class="table-wrap"><table class="t">
                <thead><tr><th>Faculty</th><th>Role</th><th>Groups</th><th>Projects</th><th>To review</th></tr></thead>
                <tbody>
                @foreach ($faculty as $f)
                    <tr>
                        <td><span style="display:flex;align-items:center;gap:10px"><x-avatar :user="$f->user" :size="30" /> {{ $f->user->name }}</span></td>
                        <td class="small">{{ $f->user->role_label }}</td>
                        <td>{{ $f->groups }}</td>
                        <td>{{ $f->projects }}</td>
                        <td>@if ($f->pending > 0)<span class="pill pill-yellow">{{ $f->pending }}</span>@else<span class="muted">0</span>@endif</td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
        @endif
    </div>

    <div class="card">
        <div class="card-head"><h2>Projects at risk</h2><a class="small" href="{{ route('reports.index') }}">Reports</a></div>
        @forelse ($atRisk as $r)
            @if ($loop->first)<div class="rows">@endif
            <a href="{{ route('projects.show', $r->project) }}" style="color:inherit">
                <span class="rank">{{ $loop->iteration }}</span>
                <span class="grow"><b>{{ $r->project->title }}</b><span class="muted small">{{ $r->project->group->name }}</span></span>
                <span style="width:110px"><x-bar :value="$r->progress" /></span>
                <span class="pill pill-red">{{ $r->overdue }} overdue</span>
            </a>
            @if ($loop->last)</div>@endif
        @empty
            <div class="empty" style="padding:22px"><x-icon name="check" size="32" /><b>No project is behind schedule</b>Everything is on track.</div>
        @endforelse
    </div>
</div>

<div class="grid g2">
    <div class="card">
        <div class="card-head"><h2>Accounts waiting for approval</h2><a class="small" href="{{ route('users.index') }}">Open users</a></div>
        @forelse ($pendingUsers as $u)
            @if ($loop->first)<div class="rows">@endif
            <div>
                <x-avatar :user="$u" :size="38" />
                <span class="grow"><b>{{ $u->name }}</b><span class="muted small">{{ $u->email }}</span></span>
                <span class="pill pill-purple">{{ $u->role_label }}</span>
                <form method="POST" action="{{ route('users.approve', $u) }}" class="inline">@csrf<button class="btn btn-sm btn-ok" type="submit">Approve</button></form>
            </div>
            @if ($loop->last)</div>@endif
        @empty
            <p class="muted">No registrations are waiting.</p>
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
@endsection
