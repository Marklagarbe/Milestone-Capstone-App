@extends('layouts.app')
@section('title', 'Create group')
@section('content')
<div class="page-head"><div><h1>Create Group</h1><p>Set up your group first, then add members and start a project.</p></div></div>
<div class="card" style="max-width:640px">
    <form method="POST" action="{{ route('groups.store') }}">
        @csrf
        <div class="field"><label for="name">Group name</label><input id="name" type="text" name="name" value="{{ old('name') }}" placeholder="e.g. Group 3 - Capstone Team" required></div>
        <div class="field"><label for="description">Description</label><textarea id="description" name="description" placeholder="Course, section or anything your adviser should know">{{ old('description') }}</textarea></div>
        <div class="row2">
            <div class="field">
                <label for="instructor_id">Instructor</label>
                <select id="instructor_id" name="instructor_id">
                    <option value="">Not assigned yet</option>
                    @foreach ($instructors as $i)<option value="{{ $i->id }}" @selected(old('instructor_id', auth()->user()->role === 'instructor' ? auth()->id() : null) == $i->id)>{{ $i->name }}</option>@endforeach
                </select>
            </div>
            <div class="field">
                <label for="adviser_id">Adviser</label>
                <select id="adviser_id" name="adviser_id">
                    <option value="">Not assigned yet</option>
                    @foreach ($advisers as $a)<option value="{{ $a->id }}" @selected(old('adviser_id', auth()->user()->role === 'adviser' ? auth()->id() : null) == $a->id)>{{ $a->name }}</option>@endforeach
                </select>
            </div>
        </div>
        @if (auth()->user()->isStudent())
            <p class="callout small" style="margin-bottom:16px">You will be added as the group leader. You can add your teammates by email on the next page.</p>
        @endif
        <div class="actions"><button class="btn btn-primary" type="submit">Create group</button><a class="btn" href="{{ route('groups.index') }}">Cancel</a></div>
    </form>
</div>
@endsection
