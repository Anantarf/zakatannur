@props(['level' => null])

@php
    $label = \App\Models\TransactionRiskReview::levelLabel($level);

    $classes = match($level) {
        'warning' => 'ui-badge-risk-warning',
        'suspicious' => 'ui-badge-risk-suspicious',
        'normal' => 'ui-badge-risk-normal',
        default => 'ui-badge-risk-empty',
    };
@endphp

<span {{ $attributes->merge(['class' => 'ui-badge ui-badge-risk gap-1.5 ' . $classes]) }}>
    @if ($level === \App\Models\TransactionRiskReview::LEVEL_WARNING)
        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" />
        </svg>
    @elseif ($level === \App\Models\TransactionRiskReview::LEVEL_SUSPICIOUS)
        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18 18 6M6 6l12 12" />
        </svg>
    @elseif ($level === \App\Models\TransactionRiskReview::LEVEL_NORMAL)
        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m5 13 4 4L19 7" />
        </svg>
    @endif
    {{ $label }}
</span>
