<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Zakat Annur') }}</title>
        <link rel="icon" type="image/png" href="{{ asset('images/logo_zakatannur.png') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        {{-- Navbar --}}
        <nav class="bg-white border-b border-gray-100 shadow-sm">
            <div class="mx-auto max-w-6xl flex items-center justify-between px-4 py-2">
                <a href="/">
                    <x-application-logo />
                </a>
                <a href="/" class="text-[10px] font-black tracking-widest text-emerald-600 uppercase bg-emerald-50 px-3 py-1.5 rounded-lg hover:bg-emerald-100 transition-all">&larr; Beranda</a>
            </div>
        </nav>

        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-50/50">
            <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
