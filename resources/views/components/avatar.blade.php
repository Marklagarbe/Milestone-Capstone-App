@props(['user' => null, 'size' => 36])
@php
$colors = ['#2f74d6', '#7a5ad8', '#1fa97a', '#d98a00', '#e5534b', '#1796b8'];
$bg = $colors[($user->id ?? 0) % count($colors)];
@endphp
@if ($user && $user->avatar)
    <img class="avatar" style="--s:{{ $size }}px" src="{{ asset('storage/'.$user->avatar) }}" alt="{{ $user->name }}" title="{{ $user->name }}">
@else
    <span class="avatar" style="--s:{{ $size }}px;background:{{ $bg }}" title="{{ $user?->name }}">{{ $user?->initials ?? '?' }}</span>
@endif
