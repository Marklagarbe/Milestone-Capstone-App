@extends('layouts.app')
@section('title', $project->title)
@section('content')
@php $isMember = $project->group->members->contains('id', auth()->id()); @endphp
<div class="page-head">
    <div>
        <div class="actions" style="margin-bottom:8px"><x-pill :status="$project->type" /><x-pill :status="$project->status" /></div>
        <h1>{{ $project->title }}</h1>
        <p>{{ $project->description ?: 'No description yet.' }}</p>
    </div>
    <div class="actions">
        <a class="btn" href="{{ route('reports.show', $project) }}"><x-icon name="bar-chart" size="18" /> Report</a>
        @if ($canManage)
            <a class="btn" href="{{ route('projects.edit', $project) }}"><x-icon name="edit" size="18" /> Edit</a>
            <form method="POST" action="{{ route('projects.destroy', $project) }}" class="inline" onsubmit="return confirm('Delete this project with all of its milestones, tasks and files?')">
                @csrf @method('DELETE')<button class="btn btn-danger" type="submit"><x-icon name="trash" size="18" /></button>
            </form>
        @endif
    </div>
</div>

<div class="grid g4" style="margin-bottom:18px">
    <div class="card stat" style="display:block"><div class="lbl" style="margin-bottom:8px">Overall progress</div><x-bar :value="$project->progress" /></div>
    <div class="card stat"><div class="ico ico-green"><x-icon name="flag" size="24" /></div><div><div class="lbl">Milestones approved</div><div class="num">{{ $stats['approved'] }}<span class="muted" style="font-size:16px">/{{ $stats['milestones'] }}</span></div></div></div>
    <div class="card stat"><div class="ico ico-blue"><x-icon name="check-square" size="24" /></div><div><div class="lbl">Tasks done</div><div class="num">{{ $stats['tasks_done'] }}<span class="muted" style="font-size:16px">/{{ $stats['tasks_total'] }}</span></div></div></div>
    <div class="card stat"><div class="ico ico-yellow"><x-icon name="folder" size="24" /></div><div><div class="lbl">Files</div><div class="num">{{ $stats['files'] }}</div></div></div>
</div>

<div class="grid split">
    <div class="stack">
        <div class="card">
            <div class="card-head"><h2>Phases and milestones</h2><a class="small" href="{{ route('timeline', ['project' => $project->id]) }}">View timeline</a></div>

            @forelse ($project->phases as $phase)
                <section class="phase">
                    <header>
                        <h3>{{ $phase->name }}</h3>
                        <span class="muted small">{{ $phase->milestones->where('status', 'approved')->count() }}/{{ $phase->milestones->count() }} approved</span>
                        <span style="width:90px"><x-bar :value="$phase->progress" /></span>
                    </header>
                    @foreach ($phase->milestones as $m)
                        @include('projects._milestone', ['m' => $m])
                    @endforeach
                    @if ($canManage)
                        <details style="padding:12px 18px;border-top:1px solid var(--line)">
                            <summary class="small" style="cursor:pointer;color:var(--blue-dark);font-weight:600">Add milestone to this phase</summary>
                            <form method="POST" action="{{ route('milestones.store', $project) }}" class="review-box">
                                @csrf
                                <input type="hidden" name="phase_id" value="{{ $phase->id }}">
                                <div class="row2">
                                    <div class="field"><label>Title</label><input type="text" name="title" required></div>
                                    <div class="field"><label>Due date</label><input type="date" name="due_date"></div>
                                </div>
                                <button class="btn btn-sm btn-primary" type="submit">Add milestone</button>
                            </form>
                            <form method="POST" action="{{ route('phases.update', $phase) }}" class="review-box" style="display:flex;gap:10px;align-items:center">
                                @csrf @method('PUT')
                                <input type="text" name="name" value="{{ $phase->name }}" required aria-label="Phase name">
                                <button class="btn btn-sm" type="submit">Rename phase</button>
                            </form>
                            <form method="POST" action="{{ route('phases.destroy', $phase) }}" onsubmit="return confirm('Delete this phase and its milestones?')" style="margin-top:8px">
                                @csrf @method('DELETE')<button class="btn btn-sm btn-danger" type="submit">Delete phase</button>
                            </form>
                        </details>
                    @endif
                </section>
            @empty
                <p class="muted" style="margin-bottom:12px">No phases yet. Add the first phase below.</p>
            @endforelse

            @if ($unphased->isNotEmpty())
                <section class="phase">
                    <header><h3>Other milestones</h3></header>
                    @foreach ($unphased as $m)
                        @include('projects._milestone', ['m' => $m])
                    @endforeach
                </section>
            @endif

            @if ($canManage)
                <form method="POST" action="{{ route('phases.store', $project) }}" class="filters" style="margin:6px 0 0">
                    @csrf
                    <input class="grow" type="text" name="name" placeholder="New phase name, e.g. Pilot Testing" required style="width:auto">
                    <button class="btn" type="submit"><x-icon name="plus" size="18" /> Add phase</button>
                </form>
            @endif
        </div>
    </div>

    <div class="stack">
        <div class="card">
            <div class="card-head"><h2>People</h2><a class="small" href="{{ route('groups.show', $project->group) }}">Open group</a></div>
            <div class="rows">
                @foreach ($project->group->members as $m)
                    <div><x-avatar :user="$m" :size="36" /><span class="grow"><b>{{ $m->name }}</b></span><x-pill :status="$m->pivot->role" /></div>
                @endforeach
                @if ($project->group->instructor)
                    <div><x-avatar :user="$project->group->instructor" :size="36" /><span class="grow"><b>{{ $project->group->instructor->name }}</b></span><span class="pill pill-purple">Instructor</span></div>
                @endif
                @if ($project->group->adviser)
                    <div><x-avatar :user="$project->group->adviser" :size="36" /><span class="grow"><b>{{ $project->group->adviser->name }}</b></span><span class="pill pill-purple">Adviser</span></div>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-head"><h2>Comments and feedback</h2></div>
            @forelse ($feedback as $f)
                <div class="fb {{ $f->decision }}">
                    <b>{{ $f->author?->name }}</b> <x-pill :status="$f->decision" />
                    <span class="muted small">{{ $f->created_at->diffForHumans() }}</span>
                    @if ($f->milestone)<div class="small muted">On {{ $f->milestone->title }}</div>@endif
                    @if ($f->document)<div class="small muted">On file: <a href="{{ route('documents.show', $f->document) }}">{{ $f->document->title }}</a></div>@endif
                    <div>{{ $f->body }}</div>
                </div>
            @empty
                <p class="muted">No comments yet.</p>
            @endforelse
            <form method="POST" action="{{ route('feedback.store', $project) }}" style="margin-top:14px">
                @csrf
                <div class="field"><textarea name="body" style="min-height:70px" placeholder="Write a comment for the group" required></textarea></div>
                <div class="filters" style="margin:0">
                    <select name="milestone_id" aria-label="Milestone"><option value="">General comment</option>@foreach ($project->milestones as $m)<option value="{{ $m->id }}">{{ str($m->title)->limit(40) }}</option>@endforeach</select>
                    <button class="btn btn-primary" type="submit">Post comment</button>
                </div>
            </form>
        </div>

        <div class="card">
            <div class="card-head"><h2>Recent activity</h2></div>
            @forelse ($activities as $a)
                @if ($loop->first)<div class="rows">@endif
                <div><x-avatar :user="$a->user" :size="32" /><span class="grow"><span><b style="display:inline">{{ $a->user?->first_name }}</b> {{ $a->description }}</span><span class="small muted">{{ $a->created_at->diffForHumans() }}</span></span></div>
                @if ($loop->last)</div>@endif
            @empty
                <p class="muted">Nothing yet.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
