<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $brand }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo_zakatannur.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script id="public-home-config" type="application/json">
        {!! json_encode([
            'openLogin' => $errors->any() || request()->has('login'),
            'selectedYear' => $selectedYear,
            'items' => $summaryData['items'] ?? [],
            'totals' => $summaryData['totals'] ?? [],
            'latestTransactionAt' => $summaryData['latest_transaction_at_wib'] ?? null,
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
<body class="min-h-screen bg-[#eef4f1] text-slate-800 flex flex-col font-sans antialiased relative overflow-x-hidden pb-2 sm:pb-3"
    x-data="publicHome"
    :class="{ 'overflow-hidden': openLogin }">
    <div class="absolute inset-0 pointer-events-none bg-[radial-gradient(circle_at_top_left,rgba(20,184,166,0.12),transparent_30%),radial-gradient(circle_at_80%_10%,rgba(148,163,184,0.10),transparent_24%),linear-gradient(180deg,#eef4f1_0%,#e8f0ec_46%,#f4f8f6_100%)]"></div>
    <div class="absolute inset-0 pointer-events-none opacity-[0.16] [background-image:linear-gradient(rgba(15,118,110,0.08)_1px,transparent_1px),linear-gradient(90deg,rgba(15,118,110,0.06)_1px,transparent_1px)] [background-size:44px_44px]"></div>

    @include('public.partials.notification')
    @include('public._login_modal')
    @include('public.partials.nav')

    <main class="public-shell-gap flex-1 relative z-10 ui-page-container">
        <div x-show="activeTab === 'laporan'" x-collapse.duration.500ms class="mb-2 sm:mb-3">
            @include('public.partials.header')
        </div>

        <div class="transition-all duration-500 mb-3 sm:mb-4 relative z-10">
            <div>
                @include('public.partials.beranda-tab')
                @include('public.partials.laporan-tab')
                @include('public.partials.grafik-tab')

                <div class="hidden">
                    <canvas id="historicalChart"></canvas>
                </div>
            </div>
        </div>
    </main>

    @include('public.partials.footer')

    <x-chatbot-widget />
</body>
</html>
