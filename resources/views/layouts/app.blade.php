<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') | ThesisCap</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/theme.css') }}?v={{ filemtime(public_path('css/theme.css')) }}">
</head>
<body>
<div class="app-bg" aria-hidden="true"><i></i><i></i><i></i></div>
@php
    $me = auth()->user();
    $unread = $me->unreadAlertCount();
    $links = [
        ['dashboard', 'Dashboard', 'grid', 'dashboard'],
        ['projects.index', 'Projects', 'layers', 'projects.*'],
        ['groups.index', 'Groups', 'users', 'groups.*'],
        ['tasks.index', 'Tasks', 'check-square', 'tasks.*'],
        ['documents.index', 'Files & Documents', 'folder', 'documents.*'],
        ['timeline', 'Timeline', 'timeline', 'timeline'],
        ['feedback.index', 'Feedback', 'message', 'feedback.*'],
        ['reports.index', 'Reports', 'bar-chart', 'reports.*'],
        ['notifications.index', 'Notifications', 'bell', 'notifications.*'],
        ['profile.edit', 'Profile', 'user', 'profile.*'],
    ];
    $pendingUsers = 0;
    if ($me->isCoordinator()) {
        $pendingUsers = \App\Models\User::where('approved', false)->count();
        array_splice($links, 9, 0, [['users.index', 'Users', 'users', 'users.*']]);
    }
@endphp
<div class="app" id="app">
    <aside class="sidebar">
        <a class="brand" href="{{ route('dashboard') }}"><x-logo /> ThesisCap</a>
        <nav class="nav" aria-label="Main">
            @foreach ($links as [$route, $label, $icon, $pattern])
                <a href="{{ route($route) }}" class="{{ request()->routeIs($pattern) ? 'active' : '' }}">
                    <x-icon :name="$icon" /> {{ $label }}
                    @if ($route === 'notifications.index' && $unread > 0)
                        <span class="badge">{{ $unread }}</span>
                    @endif
                    @if ($route === 'users.index' && $pendingUsers > 0)
                        <span class="badge">{{ $pendingUsers }}</span>
                    @endif
                </a>
            @endforeach
        </nav>
        <div class="nav nav-bottom">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"><x-icon name="logout" /> Logout</button>
            </form>
        </div>
    </aside>

    <div class="main">
        <header class="topbar">
            <button class="menu-btn" type="button" id="menuBtn" aria-label="Open menu"><x-icon name="menu" size="24" /></button>
            <form class="search" method="GET" action="{{ route('projects.index') }}" role="search">
                <x-icon name="search" size="18" />
                <input type="search" name="q" placeholder="Search projects" value="{{ request()->routeIs('projects.index') ? request('q') : '' }}" aria-label="Search projects">
            </form>
            <div class="top-right">
                <a class="bell" href="{{ route('notifications.index') }}" aria-label="Notifications">
                    <x-icon name="bell" size="22" />@if ($unread > 0)<i></i>@endif
                </a>
                <a class="userchip" href="{{ route('profile.edit') }}">
                    <x-avatar :user="$me" :size="40" />
                    <div><b>{{ $me->name }}</b><span class="role">{{ $me->role_label }}</span></div>
                </a>
            </div>
        </header>

        <main class="content">
            @if (session('success'))
                <div class="flash ok" role="status">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="flash bad" role="alert">{{ session('error') }}</div>
            @endif
            @if ($errors->any())
                <div class="flash bad" role="alert">
                    Please fix the following:
                    <ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>

<script>
    document.getElementById('menuBtn').addEventListener('click', function () {
        document.getElementById('app').classList.toggle('open');
    });
    document.querySelectorAll('[data-open]').forEach(function (b) {
        b.addEventListener('click', function () { document.getElementById(b.dataset.open).showModal(); });
    });
    document.querySelectorAll('dialog').forEach(function (d) {
        d.addEventListener('click', function (e) { if (e.target === d) d.close(); });
        d.querySelectorAll('[data-close]').forEach(function (b) { b.addEventListener('click', function () { d.close(); }); });
    });
    @if (old('_dialog'))
        (function () { var d = document.getElementById(@json(old('_dialog'))); if (d) d.showModal(); })();
    @endif
</script>
<script src="{{ asset('js/theme.js') }}"></script>
@stack('scripts')
</body>
</html>
