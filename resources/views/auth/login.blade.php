@extends('layouts.auth')
@section('title', 'Login')
@section('card')
<header class="a-head">
    <h2>Welcome back</h2>
    <p>Sign in to continue to your dashboard.</p>
</header>

@if ($errors->any())
    <div class="a-alert" role="alert">{{ $errors->first() }}</div>
@endif
@if (session('success'))
    <div class="a-alert ok" role="status">{{ session('success') }}</div>
@endif

<form method="POST" action="{{ route('login') }}" data-auth>
    @csrf
    <div class="a-field">
        <label for="email">Email address</label>
        <div class="a-input"><x-icon name="mail" size="18" /><input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="you@school.edu" required autofocus autocomplete="username"></div>
    </div>
    <div class="a-field">
        <label for="password">Password</label>
        <div class="a-input"><x-icon name="lock" size="18" /><input id="password" type="password" name="password" placeholder="Enter your password" required autocomplete="current-password">
            <button type="button" class="a-eye" data-toggle-pass aria-label="Show password"><x-icon name="eye" size="18" /></button></div>
    </div>
    <label class="a-check"><input type="checkbox" name="remember" value="1"> <span>Keep me signed in</span></label>
    <button class="a-btn" type="submit"><span>Login</span></button>
</form>

<p class="a-foot">New to ThesisCap? <a href="{{ route('register') }}">Create an account</a></p>

<details class="a-demo">
    <summary>Demo accounts</summary>
    <p style="margin-top:8px">Password for all: <code>password</code></p>
    <ul>
        <li>student@thesiscap.test</li>
        <li>instructor@thesiscap.test</li>
        <li>adviser@thesiscap.test</li>
        <li>coordinator@thesiscap.test</li>
    </ul>
</details>
@endsection
