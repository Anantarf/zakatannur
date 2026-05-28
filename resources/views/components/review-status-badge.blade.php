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

<span {{ $attributes->merge(['class' => 'ui-badge ui-badge-review ' . $classes]) }}>
    {{ $label }}
</span>
