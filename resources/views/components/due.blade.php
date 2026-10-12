@props(['date'])
@php
    $d = (int) now()->startOfDay()->diffInDays($date, false);
    if ($d < 0) { $label = abs($d).' '.str('day')->plural(abs($d)).' overdue'; $c = 'red'; }
    elseif ($d === 0) { $label = 'Due today'; $c = 'red'; }
    elseif ($d <= 3) { $label = "In {$d} ".str('day')->plural($d); $c = 'yellow'; }
    else { $label = "In {$d} days"; $c = 'blue'; }
@endphp
<span class="pill pill-{{ $c }}">{{ $label }}</span>
