@props(['value' => 0, 'segments' => [], 'size' => 168])
@php
$total = collect($segments)->sum(fn ($s) => $s[1]);
if ($total > 0) {
    $stops = []; $acc = 0;
    foreach ($segments as $s) {
        $from = round($acc / $total * 100, 2);
        $acc += $s[1];
        $to = round($acc / $total * 100, 2);
        $stops[] = "{$s[2]} {$from}% {$to}%";
    }
    $bg = 'conic-gradient('.implode(',', $stops).')';
} else {
    $bg = 'conic-gradient(#e8edf5 0 100%)';
}
@endphp
<div class="donut-wrap">
    <div class="donut" style="--size:{{ $size }}px;background:{{ $bg }}"><b>{{ (int) $value }}%</b></div>
    <div class="legend">
        @foreach ($segments as $s)
            <div><i style="background:{{ $s[2] }}"></i>{{ $s[0] }}<em>{{ $s[1] }}</em></div>
        @endforeach
    </div>
</div>
