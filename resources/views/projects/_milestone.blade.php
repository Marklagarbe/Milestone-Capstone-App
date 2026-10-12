<div class="ms s-{{ $m->status }}" id="milestone-{{ $m->id }}">
    <span class="dot">@if ($m->status === 'approved')<x-icon name="check" size="13" />@endif</span>
    <div class="body">
        <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap"><b>{{ $m->title }}</b><x-pill :status="$m->status" /></div>
        @if ($m->description)<p class="small muted" style="margin-top:2px">{{ $m->description }}</p>@endif
        <div class="meta">
            @if ($m->due_date)
                <span class="{{ $m->isOverdue() ? 'late' : '' }}"><x-icon name="calendar" size="14" /> Due {{ $m->due_date->format('M d, Y') }}{{ $m->isOverdue() ? ' (overdue)' : '' }}</span>
            @endif
            @if ($m->status === 'approved' && $m->approved_at)
                <span>Approved {{ $m->approved_at->format('M d, Y') }}{{ $m->approver ? ' by '.$m->approver->name : '' }}</span>
            @endif
            @if ($m->status === 'submitted' && $m->submitted_at)
                <span>Submitted {{ $m->submitted_at->diffForHumans() }}</span>
            @endif
        </div>

        @foreach ($m->feedback->take(2) as $f)
            <div class="fb {{ $f->decision }}"><b>{{ $f->author?->name }}</b> <span class="muted small">{{ $f->created_at->diffForHumans() }}</span><br>{{ $f->body }}</div>
        @endforeach

        @if ($isFaculty && $m->status === 'submitted')
            <form method="POST" action="{{ route('milestones.review', $m) }}" class="review-box">
                @csrf
                <textarea name="body" style="min-height:70px" placeholder="Feedback for the group. Required when requesting a revision."></textarea>
                <div class="actions" style="margin-top:10px">
                    <button class="btn btn-sm btn-ok" type="submit" name="decision" value="approved"><x-icon name="check" size="16" /> Approve</button>
                    <button class="btn btn-sm btn-danger" type="submit" name="decision" value="revision">Request revision</button>
                </div>
            </form>
        @endif

        @if ($canManage)
            <details style="margin-top:10px">
                <summary class="small" style="cursor:pointer;color:var(--blue-dark);font-weight:600">Edit milestone</summary>
                <form method="POST" action="{{ route('milestones.update', $m) }}" class="review-box">
                    @csrf @method('PUT')
                    <div class="row2">
                        <div class="field"><label>Title</label><input type="text" name="title" value="{{ $m->title }}" required></div>
                        <div class="field"><label>Due date</label><input type="date" name="due_date" value="{{ optional($m->due_date)->format('Y-m-d') }}"></div>
                    </div>
                    <div class="field"><label>Notes</label><textarea name="description" style="min-height:60px">{{ $m->description }}</textarea></div>
                    <button class="btn btn-sm btn-primary" type="submit">Save</button>
                </form>
                <form method="POST" action="{{ route('milestones.destroy', $m) }}" onsubmit="return confirm('Delete this milestone?')" style="margin-top:8px">
                    @csrf @method('DELETE')<button class="btn btn-sm btn-danger" type="submit"><x-icon name="trash" size="14" /> Delete milestone</button>
                </form>
            </details>
        @endif
    </div>
    <div class="ops">
        @if ($isMember && $m->status === 'pending')
            <form method="POST" action="{{ route('milestones.start', $m) }}">@csrf<button class="btn btn-sm" type="submit">Start</button></form>
        @endif
        @if ($isMember && in_array($m->status, ['pending', 'in_progress', 'revision'], true))
            <form method="POST" action="{{ route('milestones.submit', $m) }}">@csrf<button class="btn btn-sm btn-primary" type="submit">{{ $m->status === 'revision' ? 'Resubmit' : 'Submit for review' }}</button></form>
        @endif
    </div>
</div>
