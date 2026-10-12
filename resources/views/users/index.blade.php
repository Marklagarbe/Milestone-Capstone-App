@extends('layouts.app')
@section('title', 'Users')
@section('content')
<div class="page-head"><div><h1>Users</h1><p>Approve instructor and adviser registrations before they can log in.</p></div></div>

<div class="card" style="margin-bottom:18px">
    <div class="card-head"><h2>Waiting for approval</h2><span class="muted small">{{ $pending->count() }} pending</span></div>
    @forelse ($pending as $u)
        @if ($loop->first)<div class="rows">@endif
        <div>
            <x-avatar :user="$u" :size="40" />
            <span class="grow"><b>{{ $u->name }}</b><span class="muted small">{{ $u->email }}, registered {{ $u->created_at->diffForHumans() }}</span></span>
            <span class="pill pill-purple">{{ $u->role_label }}</span>
            <form method="POST" action="{{ route('users.approve', $u) }}" class="inline">@csrf<button class="btn btn-sm btn-ok" type="submit"><x-icon name="check" size="16" /> Approve</button></form>
            <form method="POST" action="{{ route('users.reject', $u) }}" class="inline" onsubmit="return confirm('Reject and remove this registration?')">@csrf @method('DELETE')<button class="btn btn-sm btn-danger" type="submit">Reject</button></form>
        </div>
        @if ($loop->last)</div>@endif
    @empty
        <p class="muted">No registrations are waiting.</p>
    @endforelse
</div>

<div class="card flush">
    <div class="card-head"><h2>All users</h2></div>
    <div class="table-wrap"><table class="t">
        <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>ID number</th><th>Joined</th></tr></thead>
        <tbody>
        @foreach ($users as $u)
            <tr>
                <td><span style="display:flex;align-items:center;gap:10px"><x-avatar :user="$u" :size="30" /> {{ $u->name }}</span></td>
                <td class="small">{{ $u->email }}</td>
                <td><span class="pill pill-{{ $u->isStudent() ? 'blue' : 'purple' }}">{{ $u->role_label }}</span></td>
                <td class="small">{{ $u->id_number }}</td>
                <td class="small nowrap">{{ $u->created_at->format('M d, Y') }}</td>
            </tr>
        @endforeach
        </tbody>
    </table></div>
</div>
@endsection
