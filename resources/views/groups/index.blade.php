@extends('layouts.app')
@section('title', 'Groups')
@section('content')
<div class="page-head">
    <div><h1>Groups</h1><p>Thesis and capstone groups, their members and projects.</p></div>
    <div class="actions"><a class="btn btn-primary" href="{{ route('groups.create') }}"><x-icon name="plus" size="18" /> Create Group</a></div>
</div>

<div class="grid g3" style="margin-bottom:18px">
    <div class="card stat"><div class="ico ico-blue"><x-icon name="users" size="24" /></div><div><div class="lbl">Groups</div><div class="num c-blue">{{ $groups->count() }}</div></div></div>
    <div class="card stat"><div class="ico ico-yellow"><x-icon name="layers" size="24" /></div><div><div class="lbl">Active projects</div><div class="num c-yellow">{{ $activeProjects }}</div></div></div>
    <div class="card stat"><div class="ico ico-green"><x-icon name="user" size="24" /></div><div><div class="lbl">Students</div><div class="num c-green">{{ $memberCount }}</div></div></div>
</div>

@if ($groups->isEmpty())
    <div class="card empty">
        <x-icon name="users" size="36" />
        <b>No groups yet</b>
        Create a group, add your teammates, then start your project.
    </div>
@else
    <div class="grid g3">
        @foreach ($groups as $g)
            <div class="card group-card">
                <div class="card-head" style="margin:0"><h3>{{ $g->name }}</h3><x-icon name="chevron-right" size="18" /></div>
                @forelse ($g->projects as $p)
                    <div><b>{{ $p->title }}</b><div style="margin-top:6px"><x-bar :value="$p->progress" /></div></div>
                @empty
                    <p class="muted small">No project yet</p>
                @endforelse
                <div class="avatars">@foreach ($g->members->take(5) as $m)<x-avatar :user="$m" :size="34" />@endforeach</div>
                <p class="small muted">{{ $g->members->count() }} {{ str('member')->plural($g->members->count()) }}. Instructor: {{ $g->instructor?->name ?? 'not set' }}</p>
                <a class="btn btn-sm" style="align-self:flex-start" href="{{ route('groups.show', $g) }}">View group</a>
            </div>
        @endforeach
    </div>
@endif
@endsection
