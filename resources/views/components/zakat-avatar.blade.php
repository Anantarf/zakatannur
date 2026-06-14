@props([
    'size' => 'md',
    'variant' => 'solid',
])

@php
    $sizeMap = [
        'sm' => 'h-8 w-8',
        'md' => 'h-10 w-10',
        'lg' => 'h-12 w-12',
    ];
    $containerClass = $sizeMap[$size] ?? $sizeMap['md'];

    $iconSize = match ($size) {
        'sm' => 18,
        'md' => 22,
        'lg' => 28,
        default => 22,
    };
@endphp

<span
    {{ $attributes->merge(['class' => 'zakat-avatar relative inline-flex shrink-0 items-center justify-center overflow-hidden rounded-full ' . $containerClass]) }}
    aria-hidden="true"
>
    @if ($variant === 'light')
        <span class="absolute inset-0 rounded-full bg-gradient-to-br from-white via-brand-50 to-brand-100"></span>
        <span class="absolute inset-0 rounded-full ring-1 ring-inset ring-brand-200/70"></span>
    @else
        <span class="absolute inset-0 rounded-full bg-gradient-to-br from-brand-500 via-brand-600 to-brand-700"></span>
        <span class="absolute inset-0 rounded-full ring-1 ring-inset ring-brand-700/40"></span>
    @endif

    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 32 32"
        width="{{ $iconSize }}"
        height="{{ $iconSize }}"
        class="relative z-10 drop-shadow-sm"
        fill="none"
    >
        <g stroke="{{ $variant === 'light' ? '#0f766e' : '#ffffff' }}" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
            <path d="M5 20 Q16 14 27 20" />
            <path d="M5 20 V24 H27 V20" />
            <path d="M11 20 V16 Q11 12 16 12 Q21 12 21 16 V20" />
            <path d="M16 9.5 V4" />
            <path d="M12.5 5.5 Q16 3.5 19.5 5.5 Q19.5 7.2 16 9 Q12.5 7.2 12.5 5.5 Z" fill="{{ $variant === 'light' ? '#0f766e' : '#ffffff' }}" />
        </g>
    </svg>
</span>
