@props([
    'type' => 'text', // text, title, avatar, image, card
    'class' => '',
])

@php
    $baseClass = 'ui-skeleton animate-pulse bg-slate-200 rounded';
    
    $typeClasses = [
        'text' => 'h-4 w-full',
        'title' => 'h-6 w-3/4',
        'avatar' => 'h-10 w-10 rounded-full',
        'image' => 'h-48 w-full rounded-xl',
        'card' => 'h-32 w-full rounded-xl',
    ];

    $finalClass = $baseClass . ' ' . ($typeClasses[$type] ?? $typeClasses['text']) . ' ' . $class;
@endphp

<div {{ $attributes->merge(['class' => $finalClass]) }}></div>
