@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
@php
    $me = auth()->user();
    $hour = now()->hour;
    $greet = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
    $currentFound = false;
@endphp

<section class="hero">
    <div>
        <p class="hero-date">{{ now()->format('l, F j, Y') }}</p>
        <h1>{{ $greet }}, {{ $me->first_name }}!</h1>
        <p class="hero-sub">
            @if ($nextStep)
                Your next step is <b>{{ $nextStep->title }}</b>@if ($nextStep->due_date), due {{ $nextStep->due_date->format('M d') }}@endif.
            @elseif ($primary)
                Every milestone of your project is approved. Great work!
            @else
                Start by creating your group, then add your thesis or capstone project.
            @endif
        </p>
        <div class="hero-chips">
            <span class="hero-chip"><x-icon name="check-square" size="16" /> {{ $pendingTasks + $activeTasks }} open {{ str('task')->plural($pendingTasks + $activeTasks) }}</span>
            <span class="hero-chip"><x-icon name="flag" size="16" /> {{ $approvedCount }} of {{ $milestoneCount }} milestones approved</span>
        </div>
        <div class="actions">
            @if ($primary)
                <a class="btn btn-light" href="{{ route('projects.show', $primary) }}"><x-icon name="layers" size="18" /> Open my project</a>
                <a class="btn btn-glass" href="{{ route('tasks.index', ['mine' => 1]) }}"><x-icon name="check-square" size="18" /> My tasks</a>
                <a class="btn btn-glass" href="{{ route('documents.index') }}"><x-icon name="upload" size="18" /> Upload a file</a>
            @else
                <a class="btn btn-light" href="{{ route('groups.create') }}"><x-icon name="users" size="18" /> Create a group</a>
                <a class="btn btn-glass" href="{{ route('projects.create') }}"><x-icon name="plus" size="18" /> New project</a>
            @endif
        </div>
    </div>
    <div class="ring-wrap"><div class="ring" style="--p:{{ $overall }}"><b>{{ $overall }}%</b></div>Overall progress</div>
</section>

<div class="grid g4" style="margin-bottom:18px">
    <div class="card stat"><div class="ico ico-blue"><x-icon name="clock" size="24" /></div><div><div class="lbl">Pending tasks</div><div class="num">{{ $pendingTasks }}</div></div></div>
    <div class="card stat"><div class="ico ico-yellow"><x-icon name="history" size="24" /></div><div><div class="lbl">In progress</div><div class="num c-yellow">{{ $activeTasks }}</div></div></div>
    <div class="card stat"><div class="ico ico-green"><x-icon name="check-square" size="24" /></div><div><div class="lbl">Completed tasks</div><div class="num c-green">{{ $doneTasks }}</div></div></div>
    <div class="card stat"><div class="ico ico-purple"><x-icon name="bar-chart" size="24" /></div><div><div class="lbl">Overall progress</div><div class="num c-purple">{{ $overall }}%</div></div></div>
</div>

<div class="grid split">
    <div class="stack">
        @if ($primary)
            <div class="card">
                <div class="card-head">
                    <div><h2>{{ $primary->title }}</h2><span class="muted small">{{ $primary->group->name }}</span></div>
                    <x-pill :status="$primary->status" />
                </div>
                @if ($phaseSteps->isNotEmpty())
                    <div class="steps">
                        @foreach ($phaseSteps as $i => $ph)
                            @php
                                $done = $ph->total > 0 && $ph->done === $ph->total;
                                $cls = $done ? 'done' : (! $currentFound ? 'cur' : '');
                                if (! $done) { $currentFound = true; }
                            @endphp
                            <div class="step {{ $cls }}">
                                <div class="pt">@if ($done)<x-icon name="check" size="16" />@else{{ $i + 1 }}@endif</div>
                                <span>{{ $ph->name }}</span>
                                <small>{{ $ph->done }}/{{ $ph->total }} approved</small>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="muted">This project has no phases yet.</p>
                @endif
                <div style="margin-top:18px"><x-bar :value="$primary->progress" /></div>
            </div>
        @else
            <div class="card empty">
                <x-icon name="layers" size="36" />
                <b>No project yet</b>
                Create a group with your teammates, then start your thesis or capstone project.
            </div>
        @endif

        <div class="card">
            <div class="card-head"><h2>Upcoming deadlines</h2><a class="small" href="{{ route('timeline') }}">View timeline</a></div>
            @forelse ($upcoming as $m)
                @if ($loop->first)<div class="rows">@endif
                <a href="{{ route('projects.show', $m->project) }}" style="color:inherit">
                    <span class="ico ico-{{ $m->isOverdue() ? 'red' : 'blue' }}"><x-icon name="flag" size="20" /></span>
                    <span class="grow"><b>{{ $m->title }}</b><span class="muted small">{{ $m->project->title }}, {{ $m->due_date->format('M d, Y') }}</span></span>
                    <x-due :date="$m->due_date" />
                </a>
                @if ($loop->last)</div>@endif
            @empty
                <p class="muted">No upcoming milestone deadlines.</p>
            @endforelse
        </div>

        <div class="card">
            <div class="card-head"><h2>My open tasks</h2><a class="small" href="{{ route('tasks.index', ['mine' => 1]) }}">See all</a></div>
            @forelse ($myTasks as $t)
                @if ($loop->first)<div class="rows">@endif
                <a href="{{ route('tasks.show', $t) }}" style="color:inherit">
                    <span class="ico ico-yellow"><x-icon name="check-square" size="20" /></span>
                    <span class="grow"><b>{{ $t->title }}</b><span class="muted small">{{ $t->project->title }}</span></span>
                    @if ($t->due_date)<x-due :date="$t->due_date" />@endif
                    <span style="width:110px"><x-bar :value="$t->progress" /></span>
                </a>
                @if ($loop->last)</div>@endif
            @empty
                <p class="muted">You have no open tasks. Nice work!</p>
            @endforelse
        </div>
    </div>

    <div class="stack">
        <div class="card">
            <div class="card-head"><h2>Milestones</h2></div>
            <x-donut :value="$overall" :segments="$segments" :size="150" />
        </div>
        <div class="card">
            <div class="card-head"><h2>Latest feedback</h2><a class="small" href="{{ route('feedback.index') }}">View all</a></div>
            @forelse ($feedback as $f)
                @if ($loop->first)<div class="rows">@endif
                <div>
                    <x-avatar :user="$f->author" :size="36" />
                    <span class="grow"><b>{{ $f->author?->name }} <x-pill :status="$f->decision" /></b><span class="small muted">{{ str($f->body)->limit(90) }}</span></span>
                </div>
                @if ($loop->last)</div>@endif
            @empty
                <p class="muted">No feedback yet.</p>
            @endforelse
        </div>
        <div class="card">
            <div class="card-head"><h2>Recent activities</h2></div>
            @forelse ($activities as $a)
                @if ($loop->first)<div class="rows">@endif
                <div>
                    <x-avatar :user="$a->user" :size="34" />
                    <span class="grow"><span><b style="display:inline">{{ $a->user?->first_name }}</b> {{ $a->description }}</span><span class="small muted">{{ $a->created_at->diffForHumans() }}</span></span>
                </div>
                @if ($loop->last)</div>@endif
            @empty
                <p class="muted">Activity will show up here.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
