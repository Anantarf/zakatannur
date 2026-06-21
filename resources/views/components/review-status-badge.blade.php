@props(['status' => null])

@php
    $label = match ($status) {
        \App\Models\TransactionRiskReview::REVIEW_BELUM_DITINJAU => 'Belum Review',
        default => \App\Models\TransactionRiskReview::reviewStatusLabel($status),
    };

    $classes = match($status) {
        'perlu_tindak_lanjut' => 'ui-badge-review-followup',
        'aman' => 'ui-badge-review-safe',
        default => 'ui-badge-review-pending',
    };
@endphp

<span {{ $attributes->merge(['class' => 'ui-badge ui-badge-review gap-1.5 ' . $classes]) }}>
    @if ($status === \App\Models\TransactionRiskReview::REVIEW_PERLU_TINDAK_LANJUT)
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
    @elseif ($status === \App\Models\TransactionRiskReview::REVIEW_AMAN)
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 13 4 4L19 7" />
        </svg>
    @else
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6l4 2m4-2a8 8 0 1 1-16 0 8 8 0 0 1 16 0z" />
        </svg>
    @endif
    {{ $label }}
</span>
