@extends('layouts.auth')
@section('title', 'Create your account')
@section('card_class', 'wide')
@section('card')
<header class="a-head">
    <h2>Create your account</h2>
    <p>Start tracking your thesis or capstone in minutes.</p>
</header>

@if ($errors->any())
    <div class="a-alert" role="alert"><ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
@endif

<form method="POST" action="{{ route('register') }}" data-auth>
    @csrf
    <div class="a-row">
        <div class="a-field">
            <label for="first_name">First name</label>
            <div class="a-input"><x-icon name="user" size="18" /><input id="first_name" type="text" name="first_name" value="{{ old('first_name') }}" required autofocus autocomplete="given-name"></div>
        </div>
        <div class="a-field">
            <label for="last_name">Last name</label>
            <div class="a-input"><x-icon name="user" size="18" /><input id="last_name" type="text" name="last_name" value="{{ old('last_name') }}" required autocomplete="family-name"></div>
        </div>
    </div>
    <div class="a-field">
        <label for="email">Email address</label>
        <div class="a-input"><x-icon name="mail" size="18" /><input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="you@school.edu" required autocomplete="email"></div>
    </div>
    <div class="a-field">
        <label for="role">I am a</label>
        <div class="a-input"><x-icon name="users" size="18" />
            <select id="role" name="role" required>
                <option value="student" @selected(old('role', 'student') === 'student')>Student (thesis or capstone)</option>
                <option value="instructor" @selected(old('role') === 'instructor')>Instructor</option>
                <option value="adviser" @selected(old('role') === 'adviser')>Adviser</option>
            </select>
        </div>
        <span class="a-hint">Instructor and adviser accounts must be approved by the project coordinator before they can log in.</span>
    </div>
    <div class="a-row">
        <div class="a-field">
            <label for="password">Password</label>
            <div class="a-input"><x-icon name="lock" size="18" /><input id="password" type="password" name="password" placeholder="At least 8 characters" required minlength="8" autocomplete="new-password">
                <button type="button" class="a-eye" data-toggle-pass aria-label="Show password"><x-icon name="eye" size="18" /></button></div>
        </div>
        <div class="a-field">
            <label for="password_confirmation">Confirm password</label>
            <div class="a-input"><x-icon name="lock" size="18" /><input id="password_confirmation" type="password" name="password_confirmation" placeholder="Repeat password" required autocomplete="new-password">
                <button type="button" class="a-eye" data-toggle-pass aria-label="Show password"><x-icon name="eye" size="18" /></button></div>
        </div>
    </div>
    <label class="a-check"><input type="checkbox" name="terms" value="1" @checked(old('terms')) required> <span>I agree to the Terms of Service and Privacy Policy</span></label>
    <button class="a-btn" type="submit"><span>Register</span></button>
</form>

<p class="a-foot">Already have an account? <a href="{{ route('login') }}">Login</a></p>
@endsection
