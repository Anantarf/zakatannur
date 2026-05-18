@props(['level' => null])

@php
    $label = match($level) {
        'suspicious' => 'Suspicious',
        'warning' => 'Warning',
        'normal' => 'Normal',
        default => 'Belum Analisis',
    };

    $classes = match($level) {
        'suspicious' => 'ui-badge-risk-suspicious',
        'warning' => 'ui-badge-risk-warning',
        'normal' => 'ui-badge-risk-normal',
        default => 'ui-badge-risk-empty',
    };
@endphp

<span {{ $attributes->merge(['class' => 'ui-badge ui-badge-risk ' . $classes]) }}>
    {{ $label }}
</span>
