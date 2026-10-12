@extends('layouts.app')
@section('title', 'Reports')
@section('content')
<div class="page-head">
    <div><h1>Progress and Completion Reports</h1><p>Compare projects, then open a printable report for any of them.</p></div>
    <div class="actions"><a class="btn btn-primary" href="{{ route('reports.export', request()->query()) }}"><x-icon name="download" size="18" /> Export CSV</a></div>
</div>

<div class="grid g4" style="margin-bottom:18px">
    <div class="card stat"><div class="ico ico-blue"><x-icon name="layers" size="24" /></div><div><div class="lbl">Projects</div><div class="num">{{ $summary['projects'] }}</div></div></div>
    <div class="card stat"><div class="ico ico-green"><x-icon name="check" size="24" /></div><div><div class="lbl">Completed</div><div class="num c-green">{{ $summary['completed'] }}</div></div></div>
    <div class="card stat"><div class="ico ico-purple"><x-icon name="bar-chart" size="24" /></div><div><div class="lbl">Average progress</div><div class="num c-purple">{{ $summary['avg'] }}%</div></div></div>
    <div class="card stat"><div class="ico ico-red"><x-icon name="alert" size="24" /></div><div><div class="lbl">Overdue items</div><div class="num c-red">{{ $summary['overdue'] }}</div></div></div>
</div>

<form class="filters" method="GET">
    <select name="status" onchange="this.form.submit()" aria-label="Status">
        <option value="">All statuses</option>
        @foreach (['proposal' => 'Proposal', 'in_progress' => 'In Progress', 'under_review' => 'Under Review', 'completed' => 'Completed'] as $k => $v)<option value="{{ $k }}" @selected(request('status') === $k)>{{ $v }}</option>@endforeach
    </select>
</form>

<div class="card flush">
    @if ($rows->isEmpty())
        <div class="empty"><x-icon name="bar-chart" size="36" /><b>No projects to report on</b></div>
    @else
    <div class="table-wrap">
        <table class="t">
            <thead><tr><th>Project</th><th>Status</th><th style="width:160px">Progress</th><th>Milestones</th><th>Tasks</th><th>Overdue</th><th>Files</th><th></th></tr></thead>
            <tbody>
            @foreach ($rows as $r)
                <tr>
                    <td><b>{{ $r->project->title }}</b><div class="small muted">{{ $r->project->group->name }}</div></td>
                    <td><x-pill :status="$r->project->status" /></td>
                    <td><x-bar :value="$r->progress" /></td>
                    <td>{{ $r->approved }}/{{ $r->milestones }}</td>
                    <td>{{ $r->tasksDone }}/{{ $r->tasks }}</td>
                    <td class="{{ $r->overdue ? 'tag-late' : '' }}">{{ $r->overdue }}</td>
                    <td>{{ $r->files }}</td>
                    <td class="right"><a class="btn btn-sm" href="{{ route('reports.show', $r->project) }}">Open report</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
@endsection
