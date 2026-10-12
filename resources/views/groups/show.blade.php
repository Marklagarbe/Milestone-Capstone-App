@extends('layouts.app')
@section('title', $group->name)
@section('content')
<div class="page-head">
    <div><h1>{{ $group->name }}</h1><p>{{ $group->description ?: 'No description' }}</p></div>
    <div class="actions">
        @if ($canManage)
            <button class="btn" type="button" data-open="editGroup"><x-icon name="edit" size="18" /> Edit</button>
            <form method="POST" action="{{ route('groups.destroy', $group) }}" class="inline" onsubmit="return confirm('Delete this group and all of its projects?')">
                @csrf @method('DELETE')<button class="btn btn-danger" type="submit"><x-icon name="trash" size="18" /> Delete</button>
            </form>
        @endif
    </div>
</div>

<div class="grid split">
    <div class="stack">
        <div class="card">
            <div class="card-head"><h2>Members</h2><span class="muted small">{{ $group->members->count() }} total</span></div>
            @forelse ($group->members as $m)
                @if ($loop->first)<div class="rows">@endif
                <div>
                    <x-avatar :user="$m" :size="40" />
                    <span class="grow"><b>{{ $m->name }}</b><span class="muted small">{{ $m->email }}{{ $m->id_number ? ', '.$m->id_number : '' }}</span></span>
                    <x-pill :status="$m->pivot->role" />
                    @if ($canManage)
                        <form method="POST" action="{{ route('groups.members.destroy', [$group, $m]) }}" class="inline" onsubmit="return confirm('Remove {{ addslashes($m->name) }} from this group?')">
                            @csrf @method('DELETE')<button class="btn btn-sm btn-icon btn-danger" type="submit" aria-label="Remove member"><x-icon name="x" size="16" /></button>
                        </form>
                    @endif
                </div>
                @if ($loop->last)</div>@endif
            @empty
                <p class="muted">No members yet.</p>
            @endforelse

            @if ($canManage)
                <hr class="divider">
                <form method="POST" action="{{ route('groups.members.store', $group) }}" class="filters" style="margin:0">
                    @csrf
                    <input class="grow" type="email" name="email" placeholder="Student email address" value="{{ old('email') }}" required style="width:auto">
                    <select name="role" aria-label="Role"><option value="member">Member</option><option value="leader">Leader</option></select>
                    <button class="btn btn-primary" type="submit"><x-icon name="plus" size="18" /> Add member</button>
                </form>
                <p class="small muted" style="margin-top:8px">Students need an account first. Ask them to register, then add them by email.</p>
            @endif
        </div>

        <div class="card">
            <div class="card-head"><h2>Projects</h2>
                @if ($canManage)<a class="btn btn-sm btn-primary" href="{{ route('projects.create', ['group' => $group->id]) }}"><x-icon name="plus" size="16" /> New project</a>@endif
            </div>
            @forelse ($group->projects as $p)
                @if ($loop->first)<div class="rows">@endif
                <a href="{{ route('projects.show', $p) }}" style="color:inherit">
                    <span class="grow"><b>{{ $p->title }}</b><span class="muted small">{{ ucfirst($p->type) }}{{ $p->target_date ? ', target '.$p->target_date->format('M d, Y') : '' }}</span></span>
                    <x-pill :status="$p->status" />
                    <span style="width:140px"><x-bar :value="$p->progress" /></span>
                </a>
                @if ($loop->last)</div>@endif
            @empty
                <p class="muted">No project has been created for this group.</p>
            @endforelse
        </div>
    </div>

    <div class="card">
        <h2 style="margin-bottom:14px">Supervision</h2>
        <dl class="kv">
            <dt>Instructor</dt><dd>{{ $group->instructor?->name ?? 'Not assigned' }}</dd>
            <dt>Adviser</dt><dd>{{ $group->adviser?->name ?? 'Not assigned' }}</dd>
            <dt>Leader</dt><dd>{{ $group->leader()?->name ?? 'None' }}</dd>
            <dt>Created</dt><dd>{{ $group->created_at->format('M d, Y') }}</dd>
        </dl>
    </div>
</div>

@if ($canManage)
<dialog id="editGroup">
    <form method="POST" action="{{ route('groups.update', $group) }}">
        @csrf @method('PUT')
        <input type="hidden" name="_dialog" value="editGroup">
        <div class="dlg-head"><h2>Edit group</h2><button class="btn btn-icon" type="button" data-close aria-label="Close"><x-icon name="x" size="18" /></button></div>
        <div class="dlg-body">
            <div class="field"><label>Group name</label><input type="text" name="name" value="{{ old('name', $group->name) }}" required></div>
            <div class="field"><label>Description</label><textarea name="description">{{ old('description', $group->description) }}</textarea></div>
            <div class="row2">
                <div class="field"><label>Instructor</label>
                    <select name="instructor_id"><option value="">Not assigned</option>@foreach ($instructors as $i)<option value="{{ $i->id }}" @selected($group->instructor_id == $i->id)>{{ $i->name }}</option>@endforeach</select></div>
                <div class="field"><label>Adviser</label>
                    <select name="adviser_id"><option value="">Not assigned</option>@foreach ($advisers as $a)<option value="{{ $a->id }}" @selected($group->adviser_id == $a->id)>{{ $a->name }}</option>@endforeach</select></div>
            </div>
        </div>
        <div class="dlg-foot"><button class="btn" type="button" data-close>Cancel</button><button class="btn btn-primary" type="submit">Save changes</button></div>
    </form>
</dialog>
@endif
@endsection
