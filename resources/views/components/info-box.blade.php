@props([
    'tone' => 'info',
    'title' => null,
    'message' => null,
])

@php
    $toneClass = match ($tone) {
        'warning' => 'border-amber-200 bg-amber-50/70',
        'danger' => 'border-red-200 bg-red-50/70',
        'success' => 'border-emerald-200 bg-emerald-50/70',
        default => 'border-blue-200 bg-blue-50/70',
    };

    $iconClass = match ($tone) {
        'warning' => 'text-amber-600',
        'danger' => 'text-red-600',
        'success' => 'text-emerald-600',
        default => 'text-blue-600',
    };

    $textToneClass = match ($tone) {
        'warning' => 'text-amber-900',
        'danger' => 'text-red-900',
        'success' => 'text-emerald-900',
        default => 'text-blue-900',
    };
@endphp

<div {{ $attributes->merge(['class' => "flex items-start gap-3 rounded-lg border p-4 {$toneClass}"]) }}>
    <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-5 w-5 shrink-0 {{ $iconClass }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
    </svg>
    <div class="text-sm leading-relaxed {{ $textToneClass }}">
        @if ($title)
            <strong class="font-semibold">{{ $title }}:</strong>
        @endif
        {{ $message }}
    </div>
</div>
