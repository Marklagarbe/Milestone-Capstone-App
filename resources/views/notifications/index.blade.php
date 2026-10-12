@extends('layouts.app')
@section('title', 'Notifications')
@section('content')
<div class="page-head">
    <div><h1>Notifications</h1><p>{{ $unread }} unread. Reminders for due tasks and milestones appear here too.</p></div>
    <div class="actions">
        @if ($unread > 0)<form method="POST" action="{{ route('notifications.readAll') }}">@csrf<button class="btn" type="submit"><x-icon name="check" size="18" /> Mark all as read</button></form>@endif
    </div>
</div>
<div class="card">
    @forelse ($alerts as $a)
        @if ($loop->first)<div class="rows">@endif
        <form method="POST" action="{{ route('notifications.read', $a) }}" style="padding:0;border-top:1px solid var(--line)">
            @csrf
            <button type="submit" class="notif {{ $a->read_at ? '' : 'unread' }}">
                @php $ico = ['reminder' => ['clock', 'yellow'], 'feedback' => ['message', 'purple'], 'task' => ['check-square', 'blue'], 'milestone' => ['flag', 'green'], 'document' => ['file', 'blue']][$a->kind] ?? ['bell', 'gray']; @endphp
                <span class="ico ico-{{ $ico[1] }}" style="width:40px;height:40px;border-radius:12px;display:grid;place-items:center;flex:none"><x-icon :name="$ico[0]" size="20" /></span>
                <span class="grow"><b>{{ $a->title }}</b><span class="small muted">{{ $a->body }}</span></span>
                <span class="small muted nowrap">{{ $a->created_at->diffForHumans() }}</span>
            </button>
        </form>
        @if ($loop->last)</div>@endif
    @empty
        <div class="empty"><x-icon name="bell" size="36" /><b>You are all caught up</b>New alerts will show up here.</div>
    @endforelse
</div>
{{ $alerts->links() }}
@endsection
