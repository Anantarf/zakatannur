<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

                <title>{{ config('app.name', 'Zakat Annur') }}</title>
        <link rel="icon" type="image/png" href="{{ asset('images/logo_zakatannur.png') }}">
        <!-- Preconnect & DNS-Prefetch for Speed -->
        <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
        <link rel="dns-prefetch" href="https://fonts.bunny.net">
        <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
        <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">

        <!-- Fonts -->
        <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    @if (session('status'))
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('toast').flash(
                @json(session('status')),
                @json(session('undo_id') ? route('internal.transactions.restore', session('undo_id')) : null)
            );
        });
    </script>
    @endif

    <body class="ui-shell font-sans antialiased text-slate-900" x-data>
        <div class="fixed top-4 right-4 z-[9999] w-full max-w-lg pointer-events-none">
            <div x-show="$store.toast.show"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 -translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-500"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-90"
                 style="display: none;"
                 class="pointer-events-auto flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 rounded-2xl border border-brand-200 bg-brand-50/95 backdrop-blur-sm p-4 text-brand-900 shadow-2xl">
                <div class="flex items-center gap-3">
                    <div class="bg-brand-500 text-white rounded-full p-1 shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <span x-text="$store.toast.message" class="font-bold text-sm leading-tight"></span>
                </div>
                <div x-show="$store.toast.undoRoute" class="shrink-0 w-full sm:w-auto">
                    <form :action="$store.toast.undoRoute" method="POST">
                        @csrf
                        <button type="submit" class="w-full flex items-center justify-center gap-2 text-[10px] font-black uppercase tracking-widest text-brand-800 bg-white hover:bg-brand-100 px-4 py-2.5 rounded-xl border border-brand-200 shadow-sm transition-all hover:scale-[1.02] active:scale-95">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" /></svg>
                            Urungkan
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="ui-shell-main">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @if (isset($header))
                <header class="pt-5 sm:pt-6">
                    <div class="ui-page-header">
                        <div class="ui-page-header-card">
                            {{ $header }}
                        </div>
                    </div>
                </header>
            @endif

            <!-- Page Content -->
            <main class="pb-10">
                {{ $slot }}
            </main>
        </div>

        @stack('scripts')
    </body>
</html>
