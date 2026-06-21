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
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
    @elseif ($level === \App\Models\TransactionRiskReview::LEVEL_SUSPICIOUS)
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12" />
        </svg>
    @elseif ($level === \App\Models\TransactionRiskReview::LEVEL_NORMAL)
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 13 4 4L19 7" />
        </svg>
    @endif
    {{ $label }}
</span>
