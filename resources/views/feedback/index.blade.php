@extends('layouts.app')
@section('title', 'Feedback')
@section('content')
<div class="page-head"><div><h1>Feedback and Approvals</h1><p>Comments, approvals and revision requests across your projects.</p></div></div>

<div class="grid g3" style="margin-bottom:18px">
    <div class="card stat"><div class="ico ico-blue"><x-icon name="message" size="24" /></div><div><div class="lbl">All feedback</div><div class="num">{{ $counts['all'] }}</div></div></div>
    <div class="card stat"><div class="ico ico-green"><x-icon name="check" size="24" /></div><div><div class="lbl">Approvals</div><div class="num c-green">{{ $counts['approved'] }}</div></div></div>
    <div class="card stat"><div class="ico ico-red"><x-icon name="edit" size="24" /></div><div><div class="lbl">Revision requests</div><div class="num c-red">{{ $counts['revision'] }}</div></div></div>
</div>

<form class="filters" method="GET">
    <select name="project" onchange="this.form.submit()" aria-label="Project">
        <option value="">All projects</option>
        @foreach ($projects as $p)<option value="{{ $p->id }}" @selected(request('project') == $p->id)>{{ $p->title }}</option>@endforeach
    </select>
    <select name="decision" onchange="this.form.submit()" aria-label="Type">
        <option value="">All types</option>
        @foreach (['comment' => 'Comments', 'approved' => 'Approvals', 'revision' => 'Revision requests'] as $k => $v)<option value="{{ $k }}" @selected(request('decision') === $k)>{{ $v }}</option>@endforeach
    </select>
</form>

<div class="card">
    @forelse ($items as $f)
        @if ($loop->first)<div class="rows">@endif
        <div style="align-items:flex-start">
            <x-avatar :user="$f->author" :size="40" />
            <span class="grow">
                <span><b style="display:inline">{{ $f->author?->name ?? 'Someone' }}</b> <x-pill :status="$f->decision" /> <span class="muted small">{{ $f->created_at->diffForHumans() }}</span></span>
                <span class="small muted">
                    <a href="{{ route('projects.show', $f->project) }}">{{ $f->project->title }}</a>
                    @if ($f->milestone), {{ $f->milestone->title }}@endif
                    @if ($f->document), file <a href="{{ route('documents.show', $f->document) }}">{{ $f->document->title }}</a>@endif
                </span>
                <span style="display:block;margin-top:4px">{{ $f->body }}</span>
            </span>
        </div>
        @if ($loop->last)</div>@endif
    @empty
        <div class="empty"><x-icon name="message" size="36" /><b>No feedback yet</b>Comments and approvals from your instructor will appear here.</div>
    @endforelse
</div>
{{ $items->links() }}
@endsection
