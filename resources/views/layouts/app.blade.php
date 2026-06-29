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


        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0"></script>
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('dailyTrendChart', (config) => ({
                    statusText: "Sedang memuat grafik...",
                    
                    async init() {
                        try {
                            if (typeof Chart === 'undefined') {
                                this.statusText = "Error: Chart.js gagal dimuat. Matikan AdBlocker atau cek koneksi.";
                                return;
                            }

                            if (typeof ChartDataLabels !== 'undefined' && typeof Chart.registry !== 'undefined') {
                                try { Chart.register(ChartDataLabels); } catch (_) {}
                            }

                            const canvas = this.$refs.canvas;
                            const ctx = canvas.getContext('2d');
                            const _t = getComputedStyle(document.documentElement);
                            
                            // Parse CSS variables to ensure comma-separated values (Tailwind outputs space-separated)
                            const parseRgb = (val) => val.replace(/[\s,]+/g, ',');
                            
                            const _b6 = parseRgb(_t.getPropertyValue('--color-brand-600-rgb').trim() || '2,132,199');
                            const _b7 = parseRgb(_t.getPropertyValue('--color-brand-700-rgb').trim() || '3,105,161');
                            const _b5 = parseRgb(_t.getPropertyValue('--color-brand-500-rgb').trim() || '14,165,233');
                            const _slate = parseRgb(_t.getPropertyValue('--color-slate-300-rgb').trim() || '203,213,225');

                            const CHART_COLORS = {
                                brand: { line: `rgb(${_b6})`, lineSoft: `rgba(${_b6}, 0.92)`, gradFrom: `rgba(${_b5}, 0.2)`, label: `rgb(${_b7})` },
                            };

                            const lineColor     = CHART_COLORS.brand.line;
                            const lineColorSoft = CHART_COLORS.brand.lineSoft;
                            const gradFrom      = CHART_COLORS.brand.gradFrom;
                            const labelColor    = CHART_COLORS.brand.label;

                            const gradient = ctx.createLinearGradient(0, 0, 0, 300);
                            gradient.addColorStop(0, gradFrom);
                            gradient.addColorStop(1, 'rgba(0,0,0,0)');

                            const chartValues = config.chartValues || [];
                            const chartLabels = config.chartLabels || [];
                            const chartLabel = config.chartLabel || 'Jumlah Transaksi';

                            const sumValues = chartValues.reduce((a, b) => a + (Number(b) || 0), 0);
                            const maxValues = Math.max(...chartValues, 0);
                            const pointColors = chartValues.map(v => (v > 0 ? lineColor : `rgb(${_slate})`));
                            const avgValues = chartValues.length ? sumValues / chartValues.length : 0;
                            const lastValue = chartValues[chartValues.length - 1] ?? 0;
                            const prevValue = chartValues.length > 1 ? chartValues[chartValues.length - 2] ?? 0 : 0;
                            const delta = lastValue - prevValue;

                            if (window._dailyTrendChartInstance) {
                                window._dailyTrendChartInstance.destroy();
                            }

                            const pointSizes = chartValues.map(v => (v > 0 ? 4.5 : 2.5));

                            window._dailyTrendChartInstance = new Chart(ctx, {
                                type: 'line',
                                data: {
                                    labels: chartLabels,
                                    datasets: [{
                                        label: chartLabel,
                                        data: chartValues,
                                        borderColor: lineColor,
                                        backgroundColor: gradient,
                                        borderWidth: 3,
                                        fill: true,
                                        tension: 0.36,
                                        pointBackgroundColor: pointColors,
                                        pointBorderColor: '#ffffff',
                                        pointBorderWidth: 2.5,
                                        pointRadius: pointSizes,
                                        pointHoverRadius: 7,
                                        datalabels: {
                                            display: (ctx) => Number(ctx.dataset.data[ctx.dataIndex]) > 0,
                                            align: 'top',
                                            anchor: 'end',
                                            offset: 6,
                                            color: labelColor,
                                            backgroundColor: 'rgba(255, 255, 255, 0.94)',
                                            borderColor: labelColor,
                                            borderWidth: 1,
                                            borderRadius: 6,
                                            padding: { top: 3, bottom: 3, left: 6, right: 6 },
                                            font: { family: "'Plus Jakarta Sans', sans-serif", weight: '700', size: 10 },
                                            formatter: (value) => new Intl.NumberFormat('id-ID').format(value) + ' Transaksi',
                                        }
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    interaction: { mode: 'index', intersect: false, axis: 'x' },
                                    animation: { 
                                        duration: 1200, 
                                        easing: 'easeOutQuart',
                                        delay: (context) => {
                                            let delay = 0;
                                            if (context.type === 'data' && context.mode === 'default' && !context.dropped) {
                                                delay = context.dataIndex * 60;
                                                context.dropped = true;
                                            }
                                            return delay;
                                        }
                                    },
                                    layout: { padding: { top: 36, right: 18, bottom: 8, left: 4 } },
                                    plugins: {
                                        legend: { display: false },
                                        tooltip: {
                                            backgroundColor: '#0f172a',
                                            padding: 14,
                                            cornerRadius: 12,
                                            borderColor: 'rgba(148, 163, 184, 0.2)',
                                            borderWidth: 1,
                                            titleFont: { family: "'Plus Jakarta Sans', sans-serif", size: 12, weight: '700' },
                                            titleColor: '#94a3b8',
                                            bodyFont: { family: "'Plus Jakarta Sans', sans-serif", size: 12, weight: '600' },
                                            bodyColor: '#f0fdf4',
                                            displayColors: false,
                                            boxPadding: 6,
                                            callbacks: {
                                                title: (items) => items[0]?.label ?? '',
                                                label: (ctx) => ' ' + chartLabel + ': ' + new Intl.NumberFormat('id-ID').format(ctx.parsed.y || 0),
                                                afterBody: (items) => {
                                                    const ctx = items[0];
                                                    const currentIndex = ctx.dataIndex;
                                                    if (currentIndex === 0) return []; // tidak ada data kemarin
                                                    
                                                    const currentVal = ctx.parsed.y || 0;
                                                    const prevVal = Number(ctx.dataset.data[currentIndex - 1]) || 0;
                                                    const diff = currentVal - prevVal;
                                                    
                                                    if (diff === 0) {
                                                        if (currentVal === 0) return ['', ' Tidak ada transaksi'];
                                                        return ['', ' Stabil dibanding kemarin'];
                                                    }
                                                    
                                                    const trend = diff > 0 ? '↑ Naik' : '↓ Turun';
                                                    return [
                                                        '',
                                                        ` ${trend} ${new Intl.NumberFormat('id-ID').format(Math.abs(diff))} dari kemarin`
                                                    ];
                                                },
                                            },
                                        },
                                    },
                                    scales: {
                                        y: {
                                            beginAtZero: true,
                                            suggestedMax: Math.ceil(maxValues / 10) * 10 + 20,
                                            grid: { display: false },
                                            ticks: {
                                                stepSize: 10,
                                                color: '#334155',
                                                font: { family: "'Plus Jakarta Sans', sans-serif", size: 11, weight: '600' },
                                                padding: 8,
                                            },
                                        },
                                        x: {
                                            grid: { display: false },
                                            ticks: {
                                                color: '#334155',
                                                font: { family: "'Plus Jakarta Sans', sans-serif", size: 11, weight: '600' },
                                                padding: 6,
                                            },
                                        },
                                    },
                                },
                            });
                            this.statusText = ""; // berhasil, sembunyikan teks
                        } catch (e) {
                            console.error("Chart Error: ", e);
                            this.statusText = "Error: " + e.message;
                        }
                    }
                }));
            });
        </script>
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
        <div class="fixed top-4 right-4 z-[9999] w-full max-w-lg pointer-events-none"
             role="status" aria-live="polite" aria-atomic="true">
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
                    <div class="bg-brand-500 text-white rounded-full p-1 shadow-sm" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <span x-text="$store.toast.message" class="font-bold text-sm leading-tight"></span>
                </div>
                <div x-show="$store.toast.undoRoute" class="shrink-0 w-full sm:w-auto">
                    <form :action="$store.toast.undoRoute" method="POST">
                        @csrf
                        <button type="submit" class="ui-label flex w-full items-center justify-center gap-2 rounded-xl border border-brand-200 bg-white px-4 py-2.5 text-brand-800 shadow-sm transition-all hover:scale-[1.02] hover:bg-brand-100 active:scale-95">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" /></svg>
                            Urungkan
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="ui-shell-main">
            @include('layouts.navigation')

            <div>
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
            
            <script>
                document.addEventListener('submit', function(e) {
                    if (e.defaultPrevented) return;
                    const form = e.target;
                    if (form.target === '_blank') return;
                    form.querySelectorAll('[type="submit"]').forEach(btn => {
                        btn.disabled = true;
                    });
                });
                window.addEventListener('pageshow', function(e) {
                    if (e.persisted) {
                        document.querySelectorAll('[type="submit"]').forEach(btn => {
                            btn.disabled = false;
                        });
                    }
                });
            </script>
            @stack('scripts')
        </div>
    </body>
</html>
