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
<body class="min-h-screen bg-[#f4f8f2] pb-11 text-slate-800 flex flex-col font-sans antialiased relative overflow-x-hidden sm:pb-12"
    x-data="zakatApp()"
    :class="{ 'overflow-hidden': openLogin }">
    <div class="absolute inset-0 pointer-events-none bg-[linear-gradient(180deg,#f8fbf5_0%,#f2f7f2_42%,#f8fafc_100%)]"></div>
    <div class="absolute inset-0 pointer-events-none opacity-[0.16] [background-image:linear-gradient(rgba(15,118,110,0.05)_1px,transparent_1px),linear-gradient(90deg,rgba(15,118,110,0.05)_1px,transparent_1px)] [background-size:44px_44px]"></div>

    @include('public.partials.notification')
    @include('public._login_modal')
    @include('public.partials.nav')

    <main class="public-shell-gap flex-1 w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div x-show="activeTab !== 'grafik'" x-collapse.duration.500ms
            :class="activeTab === 'laporan' ? 'mb-2 sm:mb-3' : 'mb-2 sm:mb-3'">
            @include('public.partials.header')
        </div>

        <div class="transition-all duration-500 mb-3 sm:mb-4 relative z-10">
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
