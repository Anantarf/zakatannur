@props(['status' => null])

@php
    $label = match($status) {
        'perlu_tindak_lanjut' => 'Tindak Lanjut',
        'aman' => 'Aman',
        'belum_ditinjau' => 'Belum Review',
        default => 'Belum Review',
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
