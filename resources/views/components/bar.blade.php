@props(['value' => 0, 'green' => false])
<div class="pct"><div class="bar {{ $green ? 'green' : '' }}" style="flex:1"><i style="width:{{ max(0, min(100, (int) $value)) }}%"></i></div><span>{{ (int) $value }}%</span></div>
