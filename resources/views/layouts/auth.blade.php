<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') | ThesisCap</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="a-body">
<div class="a-bg" aria-hidden="true">
    <span class="orb o1"></span><span class="orb o2"></span><span class="orb o3"></span>
    <div class="a-grid"></div>
    <svg class="a-city" viewBox="0 0 800 500" preserveAspectRatio="xMidYMax slice">
        <path class="bld" d="M320 190 L400 120 L480 190z"/>
        <rect class="bld" x="330" y="190" width="140" height="60"/>
        <rect class="bld" x="100" y="250" width="600" height="250"/>
        @for ($c = 0; $c < 3; $c++)<rect class="win" style="animation-delay:{{ ($c * 5) % 7 / 2 }}s" x="{{ 352 + $c * 40 }}" y="204" width="20" height="30" rx="2"/>@endfor
        @for ($r = 0; $r < 5; $r++)
            @for ($c = 0; $c < 12; $c++)
                <rect class="win" style="animation-delay:{{ (($r * 7 + $c * 3) % 9) / 3 }}s" x="{{ 120 + $c * 48 }}" y="{{ 268 + $r * 44 }}" width="20" height="26" rx="2"/>
            @endfor
        @endfor
    </svg>
    <div class="a-cap"><x-logo main="#d6e6fc" accent="#8fc0ff" /></div>
    @for ($i = 0; $i < 12; $i++)
        <i class="dot" style="--x:{{ ($i * 37 + 8) % 100 }}%;--s:{{ 3 + ($i % 3) * 2 }}px;--d:{{ 14 + ($i % 5) * 3 }}s;--delay:-{{ ($i * 13) % 17 }}s"></i>
    @endfor
</div>

<main class="a-wrap">
    <section class="a-hero">
        <a class="a-brand" href="{{ route('login') }}"><x-logo /> ThesisCap</a>
        <h1>Build Your Future, <span>One Milestone</span> at a Time</h1>
        <p class="a-lead">One workspace for students, instructors and advisers to follow a thesis or capstone from proposal to final defense.</p>
        <ul class="a-points">
            <li><span class="ic"><x-icon name="timeline" size="18" /></span> Track every phase from proposal to final defense</li>
            <li><span class="ic"><x-icon name="check-square" size="18" /></span> Manage tasks, revisions and approvals in one place</li>
            <li><span class="ic"><x-icon name="users" size="18" /></span> Keep advisers and instructors in the loop</li>
        </ul>
    </section>

    <section class="a-card @yield('card_class')">
        @yield('card')
    </section>
</main>

<script>
    document.querySelectorAll('[data-toggle-pass]').forEach(function (b) {
        b.addEventListener('click', function () {
            var input = b.parentElement.querySelector('input');
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            b.classList.toggle('on', show);
            b.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        });
    });
    document.querySelectorAll('form[data-auth]').forEach(function (f) {
        f.addEventListener('submit', function () { f.querySelector('.a-btn').classList.add('loading'); });
    });
</script>
</body>
</html>
