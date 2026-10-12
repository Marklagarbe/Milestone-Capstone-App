@props(['status'])
@php
$map = [
    'pending' => ['Pending', 'gray'], 'in_progress' => ['In Progress', 'blue'], 'completed' => ['Completed', 'green'],
    'submitted' => ['Submitted', 'yellow'], 'revision' => ['Needs Revision', 'red'], 'approved' => ['Approved', 'green'],
    'draft' => ['Draft', 'gray'], 'proposal' => ['Proposal', 'purple'], 'under_review' => ['Under Review', 'yellow'],
    'low' => ['Low', 'gray'], 'medium' => ['Medium', 'yellow'], 'high' => ['High', 'red'],
    'comment' => ['Comment', 'blue'], 'thesis' => ['Thesis', 'purple'], 'capstone' => ['Capstone', 'blue'],
    'leader' => ['Leader', 'blue'], 'member' => ['Member', 'gray'],
];
[$label, $color] = $map[$status] ?? [ucfirst(str_replace('_', ' ', (string) $status)), 'gray'];
@endphp
<span class="pill pill-{{ $color }}">{{ $label }}</span>
