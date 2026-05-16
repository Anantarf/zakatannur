<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $brand }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo_zakatannur.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pusher/7.0.3/pusher.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.11.3/dist/echo.iife.js"></script>
    <script id="public-home-config" type="application/json">
        {!! json_encode([
            'openLogin' => $errors->any() || request()->has('login'),
            'selectedYear' => $selectedYear,
            'items' => $summaryData['items'] ?? [],
            'totals' => $summaryData['totals'] ?? [],
            'dailyChartData' => $dailyChartData ?? [],
            'historicalChartData' => $historicalChartData ?? [],
            'refreshIntervalSeconds' => (int) $refreshIntervalSeconds,
            'realtime' => [
                'enabled' => filled(config('broadcasting.connections.pusher.key')) && filled(config('broadcasting.connections.pusher.options.cluster')),
                'key' => config('broadcasting.connections.pusher.key'),
                'cluster' => config('broadcasting.connections.pusher.options.cluster'),
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>
</head>
<body class="pb-20 sm:pb-24 min-h-screen bg-slate-100 text-slate-800 flex flex-col font-sans antialiased relative"
    x-data="zakatApp()"
    :class="{ 'overflow-hidden': openLogin }">
    <div class="absolute inset-0 bg-gradient-to-tr from-emerald-500/10 via-transparent to-emerald-500/10 pointer-events-none"></div>

    @include('public.partials.notification')
    @include('public._login_modal')
    @include('public.partials.nav')

    <main class="flex-1 w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div x-show="activeTab !== 'grafik'" x-collapse.duration.500ms>
            @include('public.partials.header')
        </div>

        <div class="transition-all duration-500 mb-6 relative z-10">
            <div>
                @include('public.partials.beranda-tab')
                @include('public.partials.laporan-tab')
                @include('public.partials.grafik-tab')

                <div style="display: none !important;">
                    <canvas id="historicalChart"></canvas>
                </div>
            </div>
        </div>
    </main>

    @include('public.partials.footer')

    <x-chatbot-widget />
</body>
</html>
