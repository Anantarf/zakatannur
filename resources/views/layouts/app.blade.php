<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

                <title>{{ config('app.name', 'Zakat Annur') }}</title>
        <link rel="icon" type="image/png" href="{{ asset('images/logo_zakatannur.png') }}">

        <!-- Alpine Toast Style -->
        <style>
            [x-cloak] { display: none !important; }
            @keyframes slideInRight { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
            .toast-enter { animation: slideInRight 0.4s ease-out forwards; }
        </style>

        <!-- Preconnect & DNS-Prefetch for Speed -->
        <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
        <link rel="dns-prefetch" href="https://fonts.bunny.net">
        <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
        <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">

        <!-- Fonts -->
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="fixed top-4 right-4 z-[9999] w-full max-w-lg pointer-events-none">
            @if (session('status'))
                <div x-data="{ show: true }" 
                     x-init="setTimeout(() => show = false, 5000)" 
                     x-show="show" 
                     x-transition:leave="transition ease-in duration-500"
                     x-transition:leave-start="opacity-100 transform scale-100"
                     x-transition:leave-end="opacity-0 transform scale-90"
                     class="pointer-events-auto flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 rounded-2xl border border-emerald-200 bg-emerald-50/95 backdrop-blur-sm p-4 text-emerald-900 shadow-2xl animate-in fade-in slide-in-from-top-4">
                    <div class="flex items-center gap-3">
                        <div class="bg-emerald-500 text-white rounded-full p-1 shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <span class="font-bold text-sm leading-tight">{{ session('status') }}</span>
                    </div>

                    @if(session('undo_id'))
                        <form action="{{ route('internal.transactions.restore', session('undo_id')) }}" method="POST" class="shrink-0 w-full sm:w-auto">
                            @csrf
                            <button type="submit" class="w-full flex items-center justify-center gap-2 text-[10px] font-black uppercase tracking-widest text-emerald-800 bg-white hover:bg-emerald-100 px-4 py-2.5 rounded-xl border border-emerald-200 shadow-sm transition-all hover:scale-[1.02] active:scale-95">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" /></svg>
                                Urungkan
                            </button>
                        </form>
                    @endif
                </div>
            @endif
        </div>

        <div class="min-h-screen bg-gray-100">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @if (isset($header))
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endif

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
