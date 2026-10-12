@extends('layouts.app')
@section('title', 'Projects')
@section('content')
<div class="page-head">
    <div><h1>Projects</h1><p>Every thesis and capstone project you can see, from proposal to final submission.</p></div>
    <div class="actions"><a class="btn btn-primary" href="{{ route('projects.create') }}"><x-icon name="plus" size="18" /> New project</a></div>
</div>

<div class="grid g4" style="margin-bottom:18px">
    <div class="card stat"><div class="ico ico-blue"><x-icon name="layers" size="24" /></div><div><div class="lbl">All projects</div><div class="num">{{ $counts['total'] }}</div></div></div>
    <div class="card stat"><div class="ico ico-purple"><x-icon name="history" size="24" /></div><div><div class="lbl">Proposal and in progress</div><div class="num c-purple">{{ $counts['in_progress'] }}</div></div></div>
    <div class="card stat"><div class="ico ico-yellow"><x-icon name="inbox" size="24" /></div><div><div class="lbl">Under review</div><div class="num c-yellow">{{ $counts['under_review'] }}</div></div></div>
    <div class="card stat"><div class="ico ico-green"><x-icon name="check" size="24" /></div><div><div class="lbl">Completed</div><div class="num c-green">{{ $counts['completed'] }}</div></div></div>
</div>

<form class="filters" method="GET">
    <input class="grow" type="search" name="q" placeholder="Search by title" value="{{ request('q') }}">
    <select name="status" onchange="this.form.submit()" aria-label="Status">
        <option value="">All statuses</option>
        @foreach (['proposal' => 'Proposal', 'in_progress' => 'In Progress', 'under_review' => 'Under Review', 'completed' => 'Completed'] as $k => $v)
            <option value="{{ $k }}" @selected(request('status') === $k)>{{ $v }}</option>
        @endforeach
    </select>
    <button class="btn" type="submit">Search</button>
</form>

@if ($projects->isEmpty())
    <div class="card empty"><x-icon name="layers" size="36" /><b>No projects found</b>Create a project to start tracking milestones.</div>
@else
    <div class="grid g3">
        @foreach ($projects as $p)
            <a class="card group-card" href="{{ route('projects.show', $p) }}" style="color:inherit">
                <div class="card-head" style="margin:0"><x-pill :status="$p->type" /><x-pill :status="$p->status" /></div>
                <h3 style="font-size:18px">{{ $p->title }}</h3>
                <p class="muted small">{{ $p->group->name }}</p>
                <x-bar :value="$p->progress" />
                <div class="card-head" style="margin:0">
                    <div class="avatars">@foreach ($p->group->members->take(4) as $m)<x-avatar :user="$m" :size="30" />@endforeach</div>
                    <span class="small {{ ($p->days_left ?? 1) < 0 && $p->status !== 'completed' ? 'tag-late' : 'muted' }}">
                        @if ($p->status === 'completed') Completed
                        @elseif ($p->target_date) {{ $p->days_left < 0 ? abs($p->days_left).' days past target' : $p->days_left.' days left' }}
                        @else No target date @endif
                    </span>
                </div>
            </a>
        @endforeach
    </div>
@endif
@endsection
