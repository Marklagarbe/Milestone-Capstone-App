@extends('layouts.app')
@section('title', 'Profile')
@section('content')
<div class="page-head"><div><h1>Profile</h1><p>Your details are shown to your group, instructor and adviser.</p></div></div>
<div class="grid g2" style="align-items:start">
    <div class="card">
        <h2 style="margin-bottom:14px">Personal information</h2>
        <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
            @csrf @method('PUT')
            <div style="display:flex;align-items:center;gap:16px;margin-bottom:16px">
                <x-avatar :user="$user" :size="72" />
                <div class="field" style="margin:0"><label for="avatar">Profile photo</label><input id="avatar" type="file" name="avatar" accept="image/*"></div>
            </div>
            <div class="row2">
                <div class="field"><label>First name</label><input type="text" name="first_name" value="{{ old('first_name', $user->first_name) }}" required></div>
                <div class="field"><label>Last name</label><input type="text" name="last_name" value="{{ old('last_name', $user->last_name) }}" required></div>
            </div>
            <div class="row2">
                <div class="field"><label>Email</label><input type="email" value="{{ $user->email }}" disabled></div>
                <div class="field"><label>Role</label><input type="text" value="{{ $user->role_label }}" disabled></div>
            </div>
            <div class="row2">
                <div class="field"><label>{{ $user->isStudent() ? 'Student number' : 'Employee number' }}</label><input type="text" name="id_number" value="{{ old('id_number', $user->id_number) }}"></div>
                <div class="field"><label>Contact number</label><input type="text" name="contact" value="{{ old('contact', $user->contact) }}"></div>
            </div>
            <div class="row2">
                <div class="field"><label>Department</label><input type="text" name="department" value="{{ old('department', $user->department) }}"></div>
                <div class="field"><label>{{ $user->isStudent() ? 'Program' : 'Specialization' }}</label><input type="text" name="program" value="{{ old('program', $user->program) }}"></div>
            </div>
            <div class="field"><label>About</label><textarea name="bio">{{ old('bio', $user->bio) }}</textarea></div>
            <button class="btn btn-primary" type="submit">Save profile</button>
        </form>
    </div>
    <div class="card">
        <h2 style="margin-bottom:14px">Change password</h2>
        <form method="POST" action="{{ route('profile.password') }}">
            @csrf @method('PUT')
            <div class="field"><label>Current password</label><input type="password" name="current_password" required></div>
            <div class="field"><label>New password</label><input type="password" name="password" minlength="8" required></div>
            <div class="field"><label>Confirm new password</label><input type="password" name="password_confirmation" required></div>
            <button class="btn btn-primary" type="submit">Update password</button>
        </form>
    </div>
</div>
@endsection
