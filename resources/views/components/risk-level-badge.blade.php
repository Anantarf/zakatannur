@props(['level' => null])

@php
    $label = \App\Models\TransactionRiskReview::levelLabel($level);

    $classes = match($level) {
        'warning' => 'bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-600/20',
        'suspicious' => 'bg-red-50 text-red-700 ring-1 ring-inset ring-red-600/20',
        'safe' => 'bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-600/20',
        default => 'bg-slate-50 text-slate-500 ring-1 ring-inset ring-slate-500/20',
    };
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center justify-center rounded px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wider whitespace-nowrap leading-tight gap-1 ' . $classes]) }}>
    @if ($level === \App\Models\TransactionRiskReview::LEVEL_WARNING)
        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
    @elseif ($level === \App\Models\TransactionRiskReview::LEVEL_SUSPICIOUS)
        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18 18 6M6 6l12 12" />
        </svg>
    @elseif ($level === \App\Models\TransactionRiskReview::LEVEL_SAFE)
        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m5 13 4 4L19 7" />
        </svg>
    @endif
    {{ $label }}
</span>
