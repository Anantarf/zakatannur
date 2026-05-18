@props([
    'title',
    'value',
    'description' => null,
    'tone' => 'default',
])

@php
    $toneClass = match($tone) {
        'warning' => 'ui-stat-card-warning',
        'danger' => 'ui-stat-card-danger',
        'muted' => 'ui-stat-card-muted',
        'info' => 'ui-stat-card-info',
        default => 'ui-stat-card-default',
    };

    $eyebrowClass = match($tone) {
        'warning' => 'text-amber-700',
        'danger' => 'text-red-700',
        'muted' => 'text-slate-600',
        'info' => 'text-blue-700',
        default => 'text-gray-400',
    };

    $descriptionClass = match($tone) {
        'warning' => 'text-amber-700/80',
        'danger' => 'text-red-700/80',
        'muted' => 'text-slate-600',
        'info' => 'text-blue-700/80',
        default => 'text-gray-500',
    };
@endphp

<div {{ $attributes->merge(['class' => 'ui-stat-card ' . $toneClass]) }}>
    <p class="ui-stat-eyebrow {{ $eyebrowClass }}">{{ $title }}</p>
    <p class="ui-stat-value">{{ $value }}</p>
    @if ($description)
        <p class="ui-stat-description {{ $descriptionClass }}">{{ $description }}</p>
    @endif
</div>
