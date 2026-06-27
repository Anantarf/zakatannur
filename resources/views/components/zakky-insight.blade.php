@props([
    'tone' => 'info',
    'label' => 'Informasi dari Zakky',
    'message' => null,
    'items' => [],
    'generated' => false,
])

@php
    $toneClass = match ($tone) {
        'warning' => 'border-rose-200 bg-white text-rose-950',
        'attention' => 'border-amber-200 bg-white text-amber-950',
        'success' => 'border-emerald-200 bg-white text-emerald-950',
        default => 'border-emerald-200 bg-white text-slate-800',
    };

    $iconClass = match ($tone) {
        'warning' => 'text-rose-600',
        'attention' => 'text-amber-600',
        'success' => 'text-emerald-700',
        default => 'text-emerald-700',
    };
@endphp

@if ($message)
    <div {{ $attributes->merge(['class' => 'rounded-lg border p-3 shadow-sm ' . $toneClass]) }}>
        <div class="flex items-start gap-2.5">
            <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-4 w-4 shrink-0 {{ $iconClass }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 20.5a8.5 8.5 0 100-17 8.5 8.5 0 000 17z" />
            </svg>
            <div class="min-w-0">
                <p class="text-sm font-semibold text-slate-800">{{ $label }}</p>
                <p class="mt-1 text-sm leading-6 text-slate-700">{{ $message }}</p>

                @if (!empty($items))
                    <ul class="mt-2 space-y-1 text-sm leading-6 text-slate-700">
                        @foreach ($items as $item)
                            <li class="flex gap-2">
                                <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-current opacity-50"></span>
                                <span>{{ $item }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif

                @if ($generated)
                    <p class="mt-2 text-[11px] font-medium text-slate-400">AI generated</p>
                @endif
            </div>
        </div>
    </div>
@endif
