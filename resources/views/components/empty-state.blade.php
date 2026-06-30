@props([
    'title' => 'Belum ada data',
    'description' => 'Belum ada data yang tersimpan untuk ditampilkan.',
    'icon' => 'default', // default, search, document
    'class' => ''
])

@php
    $baseClass = 'flex flex-col items-center justify-center p-8 text-center bg-slate-50/50 rounded-xl border border-dashed border-slate-200';
@endphp

<div {{ $attributes->merge(['class' => $baseClass . ' ' . $class]) }}>
    @isset($iconSlot)
        @if (! $iconSlot->isEmpty())
            {{ $iconSlot }}
        @endif
    @else
        @if ($icon === 'search')
            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-slate-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
        @elseif ($icon === 'document')
            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-slate-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
        @else
            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-slate-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
            </svg>
        @endif
    @endisset
    
    <h3 class="text-sm font-semibold text-slate-700">{{ $title }}</h3>
    <p class="mt-1 text-sm text-slate-400 max-w-sm">{{ $description }}</p>

    {{ $slot }}
</div>
