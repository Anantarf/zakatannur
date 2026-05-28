@props(['level' => null])

@php
    $label = \App\Models\TransactionRiskReview::levelLabel($level);

    $classes = match($level) {
        'warning' => 'ui-badge-risk-warning',
        'normal' => 'ui-badge-risk-normal',
        default => 'ui-badge-risk-empty',
    };
@endphp

<span {{ $attributes->merge(['class' => 'ui-badge ui-badge-risk ' . $classes]) }}>
    {{ $label }}
</span>
