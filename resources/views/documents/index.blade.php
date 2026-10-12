@extends('layouts.app')
@section('title', 'Files & Documents')
@section('content')
@php
    $views = ['all' => 'All Files', 'favorites' => 'Favorites', 'mine' => 'My uploads', 'trash' => 'Trash'];
    $cat = request('category');
@endphp
<div class="page-head">
    <div><h1>Files &amp; Documents</h1><p>Proposals, chapters, manuscripts and slides with full version history.</p></div>
    <div class="actions">
        @if ($projects->isNotEmpty())
            <button class="btn btn-primary" type="button" data-open="uploadDoc"><x-icon name="upload" size="18" /> Upload file</button>
        @endif
    </div>
</div>

<div class="chips" style="margin-bottom:18px">
    @foreach ($views as $key => $label)
        <a class="chip {{ $view === $key ? 'on' : '' }}" href="{{ route('documents.index', ['view' => $key]) }}">{{ $label }}@if ($key === 'trash' && $trashCount) <em>{{ $trashCount }}</em>@endif</a>
    @endforeach
</div>

@if ($view !== 'trash')
<div class="folders">
    @foreach (\App\Models\Document::CATEGORIES as $key => $label)
        <a class="folder f-{{ $key }} {{ $cat === $key ? 'on' : '' }}" href="{{ route('documents.index', ['view' => $view, 'category' => $cat === $key ? null : $key]) }}">
            <span class="fi"></span>
            <span><b>{{ $label }}</b><br><span class="small muted">{{ $folders[$key] ?? 0 }} {{ str('file')->plural($folders[$key] ?? 0) }}</span></span>
        </a>
    @endforeach
</div>
@endif

<form class="filters" method="GET">
    <input type="hidden" name="view" value="{{ $view }}">
    @if ($cat)<input type="hidden" name="category" value="{{ $cat }}">@endif
    <input class="grow" type="search" name="q" placeholder="Search by title or file name" value="{{ request('q') }}">
    <select name="project" onchange="this.form.submit()" aria-label="Project">
        <option value="">All projects</option>
        @foreach ($projects as $p)<option value="{{ $p->id }}" @selected(request('project') == $p->id)>{{ $p->title }}</option>@endforeach
    </select>
    <select name="status" onchange="this.form.submit()" aria-label="Status">
        <option value="">Any status</option>
        @foreach (['draft' => 'Draft', 'submitted' => 'Submitted', 'approved' => 'Approved', 'revision' => 'Needs Revision'] as $k => $v)<option value="{{ $k }}" @selected(request('status') === $k)>{{ $v }}</option>@endforeach
    </select>
    <button class="btn" type="submit">Search</button>
</form>

<div class="card flush">
    @if ($documents->isEmpty())
        <div class="empty"><x-icon name="folder" size="36" /><b>{{ $view === 'trash' ? 'Trash is empty' : 'No files found' }}</b>{{ $view === 'trash' ? 'Deleted files stay here until you remove them for good.' : 'Upload a file to start building your version history.' }}</div>
    @else
        <div class="table-wrap">
            <table class="t">
                <thead><tr><th>File</th><th>Project</th><th>Version</th><th>Status</th><th>Uploaded by</th><th>Updated</th><th></th></tr></thead>
                <tbody>
                @foreach ($documents as $d)
                    @php $v = $d->latestVersion; @endphp
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:12px">
                                <span class="ext x-{{ $v?->extension }}">{{ strtoupper($v?->extension ?: 'file') }}</span>
                                <div>
                                    @if ($d->trashed())<b>{{ $d->title }}</b>@else<a href="{{ route('documents.show', $d) }}" style="color:var(--ink)"><b>{{ $d->title }}</b></a>@endif
                                    <div class="small muted">{{ $v?->original_name }} {{ $v ? '('.$v->size_label.')' : '' }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="small">{{ $d->project->title }}<div class="muted">{{ \App\Models\Document::CATEGORIES[$d->category] ?? $d->category }}</div></td>
                        <td>v{{ $v?->version ?? 1 }}</td>
                        <td><x-pill :status="$d->status" /></td>
                        <td class="small">{{ $d->uploader?->name }}</td>
                        <td class="small nowrap">{{ $d->updated_at->format('M d, Y') }}</td>
                        <td class="right nowrap">
                            @if ($d->trashed())
                                <form class="inline" method="POST" action="{{ route('documents.restore', $d) }}">@csrf<button class="btn btn-sm" type="submit">Restore</button></form>
                                <form class="inline" method="POST" action="{{ route('documents.force', $d) }}" onsubmit="return confirm('Delete this file and all of its versions for good?')">@csrf @method('DELETE')<button class="btn btn-sm btn-danger" type="submit">Delete forever</button></form>
                            @else
                                <form class="inline" method="POST" action="{{ route('documents.favorite', $d) }}">@csrf
                                    <button class="fav {{ in_array($d->id, $favoriteIds) ? 'on' : '' }}" type="submit" aria-label="Toggle favorite"><x-icon name="star" size="20" /></button></form>
                                @if ($v)<a class="btn btn-sm btn-icon" href="{{ route('documents.download', $v) }}" aria-label="Download"><x-icon name="download" size="16" /></a>@endif
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
{{ $documents->links() }}

@if ($projects->isNotEmpty())
<dialog id="uploadDoc">
    <form method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="_dialog" value="uploadDoc">
        <div class="dlg-head"><h2>Upload file</h2><button class="btn btn-icon" type="button" data-close aria-label="Close"><x-icon name="x" size="18" /></button></div>
        <div class="dlg-body">
            <div class="field"><label for="d_project">Project</label>
                <select id="d_project" name="project_id" required>
                    <option value="">Choose a project</option>
                    @foreach ($projects as $p)<option value="{{ $p->id }}" @selected(old('project_id', request('project')) == $p->id)>{{ $p->title }}</option>@endforeach
                </select></div>
            <div class="field"><label for="d_title">Title</label><input id="d_title" type="text" name="title" value="{{ old('title') }}" placeholder="e.g. Chapter 2: Review of Related Literature" required></div>
            <div class="row2">
                <div class="field"><label for="d_category">Folder</label>
                    <select id="d_category" name="category">@foreach (\App\Models\Document::CATEGORIES as $k => $l)<option value="{{ $k }}" @selected(old('category') === $k)>{{ $l }}</option>@endforeach</select></div>
                <div class="field"><label for="d_ms">Milestone</label>
                    <select id="d_ms" name="milestone_id"><option value="">None</option>
                        @foreach ($projects as $p)@foreach ($p->milestones as $m)<option data-project="{{ $p->id }}" value="{{ $m->id }}">{{ str($m->title)->limit(40) }}</option>@endforeach @endforeach
                    </select></div>
            </div>
            <div class="field"><label for="d_file">File</label><input id="d_file" type="file" name="file" required><span class="small muted">PDF, Word, PowerPoint, Excel, ZIP, images or text. Up to 20 MB.</span></div>
            <div class="field"><label for="d_desc">Notes</label><textarea id="d_desc" name="description" style="min-height:70px">{{ old('description') }}</textarea></div>
            <label class="check"><input type="checkbox" name="submit_now" value="1"> <span>Submit for instructor review right away</span></label>
        </div>
        <div class="dlg-foot"><button class="btn" type="button" data-close>Cancel</button><button class="btn btn-primary" type="submit">Upload</button></div>
    </form>
</dialog>
@push('scripts')
<script>
    (function () {
        var project = document.getElementById('d_project'), ms = document.getElementById('d_ms');
        function filter() {
            ms.querySelectorAll('option[data-project]').forEach(function (o) {
                var ok = o.dataset.project === project.value; o.hidden = !ok; o.disabled = !ok;
            });
            if (ms.selectedOptions[0] && ms.selectedOptions[0].disabled) ms.value = '';
        }
        project.addEventListener('change', filter); filter();
    })();
</script>
@endpush
@endif
@endsection
