@extends('layouts.app')
@section('title', $document->title)
@section('content')
@php $latest = $document->versions->first(); @endphp
<div class="page-head">
    <div>
        <div class="actions" style="margin-bottom:8px"><x-pill :status="$document->status" /><span class="pill pill-gray">{{ \App\Models\Document::CATEGORIES[$document->category] ?? $document->category }}</span></div>
        <h1>{{ $document->title }}</h1>
        <p><a href="{{ route('projects.show', $project) }}">{{ $project->title }}</a>{{ $document->milestone ? ', '.$document->milestone->title : '' }}</p>
    </div>
    <div class="actions">
        <a class="btn" href="{{ route('documents.index') }}">All files</a>
        <form method="POST" action="{{ route('documents.favorite', $document) }}" class="inline">@csrf
            <button class="btn" type="submit"><x-icon name="star" size="18" /> {{ $isFavorite ? 'Unfavorite' : 'Favorite' }}</button></form>
        @if ($latest)<a class="btn btn-primary" href="{{ route('documents.download', $latest) }}"><x-icon name="download" size="18" /> Download v{{ $latest->version }}</a>@endif
    </div>
</div>

<div class="grid split">
    <div class="stack">
        <div class="card flush">
            <div class="card-head"><h2>Version history</h2></div>
            <div class="table-wrap">
                <table class="t">
                    <thead><tr><th>Version</th><th>File</th><th>Uploaded by</th><th>Date</th><th>Notes</th><th></th></tr></thead>
                    <tbody>
                    @foreach ($document->versions as $v)
                        <tr>
                            <td><b>v{{ $v->version }}</b>@if ($loop->first) <span class="pill pill-blue">Latest</span>@endif</td>
                            <td class="small">{{ $v->original_name }}<div class="muted">{{ $v->size_label }}</div></td>
                            <td class="small">{{ $v->uploader?->name }}</td>
                            <td class="small nowrap">{{ $v->created_at->format('M d, Y g:i A') }}</td>
                            <td class="small">{{ $v->notes }}</td>
                            <td class="right"><a class="btn btn-sm btn-icon" href="{{ route('documents.download', $v) }}" aria-label="Download version {{ $v->version }}"><x-icon name="download" size="16" /></a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        @if ($canEdit)
        <div class="card">
            <h2 style="margin-bottom:12px">Upload a new version</h2>
            <form method="POST" action="{{ route('documents.versions.store', $document) }}" enctype="multipart/form-data">
                @csrf
                <div class="field"><label for="file">File</label><input id="file" type="file" name="file" required></div>
                <div class="field"><label for="notes">What changed?</label><textarea id="notes" name="notes" style="min-height:70px" placeholder="e.g. Revised scope and limitations based on feedback"></textarea></div>
                <label class="check" style="margin-bottom:14px"><input type="checkbox" name="submit_now" value="1"> <span>Submit for instructor review right away</span></label>
                <button class="btn btn-primary" type="submit"><x-icon name="upload" size="18" /> Upload version</button>
            </form>
        </div>
        @endif

        <div class="card">
            <h2 style="margin-bottom:12px">Instructor feedback</h2>
            @forelse ($document->feedback as $f)
                <div class="fb {{ $f->decision }}">
                    <b>{{ $f->author?->name }}</b> <x-pill :status="$f->decision" /> <span class="muted small">{{ $f->created_at->diffForHumans() }}</span>
                    <div>{{ $f->body }}</div>
                </div>
            @empty
                <p class="muted">No feedback on this file yet.</p>
            @endforelse
        </div>
    </div>

    <div class="stack">
        <div class="card">
            <h2 style="margin-bottom:14px">Details</h2>
            <dl class="kv" style="grid-template-columns:110px 1fr">
                <dt>Uploaded by</dt><dd>{{ $document->uploader?->name }}</dd>
                <dt>Created</dt><dd>{{ $document->created_at->format('M d, Y') }}</dd>
                <dt>Versions</dt><dd>{{ $document->versions->count() }}</dd>
            </dl>
            @if ($document->description)<hr class="divider"><p class="small">{{ $document->description }}</p>@endif
            @if ($canEdit)
                <hr class="divider">
                <div class="actions">
                    @if (in_array($document->status, ['draft', 'revision'], true))
                        <form method="POST" action="{{ route('documents.submit', $document) }}">@csrf<button class="btn btn-primary" type="submit">Submit for review</button></form>
                    @endif
                    <form method="POST" action="{{ route('documents.destroy', $document) }}" onsubmit="return confirm('Move this file to the trash?')">@csrf @method('DELETE')<button class="btn btn-danger" type="submit"><x-icon name="trash" size="16" /> Move to trash</button></form>
                </div>
            @endif
        </div>

        @if ($canReview)
        <div class="card">
            <h2 style="margin-bottom:12px">Review this file</h2>
            <form method="POST" action="{{ route('documents.review', $document) }}">
                @csrf
                <div class="field"><textarea name="body" style="min-height:90px" placeholder="Feedback for the group. Required when requesting a revision."></textarea></div>
                <div class="actions">
                    <button class="btn btn-ok" type="submit" name="decision" value="approved"><x-icon name="check" size="16" /> Approve</button>
                    <button class="btn btn-danger" type="submit" name="decision" value="revision">Request revision</button>
                </div>
            </form>
        </div>
        @endif
    </div>
</div>
@endsection
