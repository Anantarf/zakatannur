<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <h2 class="font-bold text-xl sm:text-2xl text-emerald-800 leading-tight flex items-center justify-center sm:justify-start gap-2 text-center sm:text-left">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
                Dashboard Pengelolaan
            </h2>
            <div class="hidden sm:block"></div>
        </div>
    </x-slot>

    <div class="py-4 sm:py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4 sm:space-y-8">
            <div class="grid grid-cols-1 gap-4 xl:grid-cols-[minmax(0,1.2fr)_minmax(300px,0.8fr)]">
                <div class="ui-card-strong p-5 sm:p-6">
                    <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
                        <div class="space-y-2">
                            <p class="text-[11px] font-black uppercase tracking-[0.24em] text-emerald-700">Overview</p>
                            <h3 class="text-xl font-black leading-tight text-slate-950 sm:text-[1.7rem]">Manajemen Operasional Zakat</h3>
                            <p class="max-w-2xl text-sm leading-6 text-slate-500">Kelola transaksi, pantau metrik penerimaan hari ini, dan akses aksi operasional dengan cepat dari satu tempat.</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <a href="{{ route('internal.transactions.create') }}" class="ui-btn ui-btn-primary w-full sm:w-auto">Input Transaksi</a>
                            <a href="{{ route('internal.transactions.index', array_filter(['year' => $year, 'period_id' => $periodId, 'metode' => $metode])) }}" class="ui-btn ui-btn-secondary w-full sm:w-auto">Buka Riwayat</a>
                        </div>
                    </div>

                    <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <div class="ui-kpi-card">
                            <p class="ui-kpi-label">Total Transaksi</p>
                            <p class="ui-kpi-value">{{ number_format($payload['totals']['jumlah_transaksi'] ?? 0, 0, ',', '.') }}</p>
                            <p class="ui-kpi-note">Jumlah transaksi yang tampil di ringkasan dashboard.</p>
                        </div>
                        <div class="ui-kpi-card">
                            <p class="ui-kpi-label">Transaksi Hari Ini</p>
                            <p class="ui-kpi-value">{{ number_format($workspace['today_count'] ?? 0, 0, ',', '.') }}</p>
                            <p class="ui-kpi-note">Transaksi valid yang tercatat hari ini.</p>
                        </div>
                        <div class="ui-kpi-card">
                            <p class="ui-kpi-label">Transaksi Terakhir</p>
                            <p class="mt-2 text-lg font-black tracking-[-0.02em] text-slate-900">
                                {{ ($workspace['latest_transaction_at'] ?? null)?->format('d/m/Y H:i') ?? '-' }}
                            </p>
                            <p class="ui-kpi-note">Waktu transaksi terbaru yang tercatat di sistem.</p>
                        </div>
                    </div>
                </div>

                <div class="ui-card p-5 sm:p-6">
                    <p class="text-[11px] font-black uppercase tracking-[0.24em] text-slate-400">Navigasi Cepat</p>
                    <div class="mt-4 grid grid-cols-1 gap-3">
                        <a href="{{ route('internal.transactions.index', array_filter(['year' => $year, 'period_id' => $periodId, 'metode' => $metode])) }}" class="ui-action-tile">
                            <p class="text-sm font-black text-slate-900">Riwayat Transaksi</p>
                            <p class="mt-1 text-sm leading-6 text-slate-600">Telusuri transaksi lengkap sesuai filter dashboard saat ini.</p>
                        </a>
                        <a href="{{ route('internal.muzakki.index') }}" class="ui-action-tile ui-action-tile-accent">
                            <p class="text-sm font-black text-emerald-900">Data Muzakki</p>
                            <p class="mt-1 text-sm leading-6 text-emerald-800">Cari data muzakki dan lihat riwayat yang sudah tercatat.</p>
                        </a>
                        <a href="{{ route('internal.transactions.trash') }}" class="ui-action-tile ui-action-tile-info">
                            <p class="text-sm font-black text-blue-900">Trash Transaksi</p>
                            <p class="mt-1 text-sm leading-6 text-blue-800">Cek transaksi yang pernah dihapus dan masih tersimpan di trash.</p>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Daily Trend Chart -->
            <div class="bg-white rounded-2xl shadow-md border border-gray-100 overflow-hidden">
                <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-50 flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4">
                    <div class="flex items-center gap-2 flex-wrap">
                        <div class="w-1.5 h-5 sm:w-2 sm:h-6 bg-emerald-500 rounded-full flex-shrink-0"></div>
                        <h3 class="font-bold text-sm sm:text-base text-gray-800">Tren Penerimaan</h3>
                        @if($chartPeriodLabel)
                            <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 border border-amber-200 px-2.5 py-0.5 text-[10px] font-bold text-amber-700 uppercase tracking-wide whitespace-nowrap">
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                Range: {{ $chartPeriodLabel }}
                            </span>
                        @endif
                    </div>

                    <form method="GET" action="{{ route('dashboard') }}" class="flex items-center justify-end gap-2 w-full sm:w-auto">
                        {{-- Keep other filters --}}
                        @if(request('year')) <input type="hidden" name="year" value="{{ request('year') }}"> @endif
                        @if(request('period_id')) <input type="hidden" name="period_id" value="{{ request('period_id') }}"> @endif
                        @if(request('metode')) <input type="hidden" name="metode" value="{{ request('metode') }}"> @endif

                        <select name="days" onchange="this.form.submit()" class="appearance-none rounded-lg border-gray-200 bg-gray-50 pl-3 pr-8 py-1.5 text-[11px] sm:text-xs font-black text-gray-500 uppercase tracking-widest focus:border-emerald-500 focus:ring-emerald-500 transition-all cursor-pointer">
                            <option value="7" @selected($activeDays == 7)>7 Hari</option>
                            <option value="14" @selected($activeDays == 14)>14 Hari</option>
                            <option value="30" @selected($activeDays == 30)>30 Hari</option>
                        </select>
                    </form>
                </div>
                <div class="p-4 sm:p-6">
                    <p class="mb-4 text-sm leading-6 text-slate-500">Visualisasi tren transaksi aktif. Untuk laporan terperinci, gunakan menu Rekapitulasi atau Riwayat.</p>
                    @if (!empty($dashboardChartSourceNote))
                        <div class="mb-4 rounded-2xl border border-sky-100 bg-sky-50 px-4 py-3 text-xs leading-relaxed text-sky-800">
                            {{ $dashboardChartSourceNote }}
                        </div>
                    @endif
                    @if (!empty($dashboardChartRange['fallback_note']))
                        <div class="mb-4 rounded-2xl border border-amber-100 bg-amber-50 px-4 py-3 text-xs leading-relaxed text-amber-800">
                            {{ $dashboardChartRange['fallback_note'] }}
                        </div>
                    @endif
                    <div class="relative h-[320px] w-full sm:h-[380px]">
                        <canvas id="dailyTrendChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Rekap Table Section -->
            <div class="bg-white rounded-2xl shadow-md border border-gray-100 overflow-hidden">
                <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-50 flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4">
                    <div class="flex items-center gap-2">
                        <div class="w-1.5 h-5 sm:w-2 sm:h-6 bg-emerald-500 rounded-full"></div>
                        <h3 class="font-bold text-sm sm:text-base text-gray-800">Rekapitulasi Zakat</h3>
                    </div>

                    <form method="GET" action="{{ route('dashboard') }}" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 w-full sm:w-auto mt-2 sm:mt-0">
                        <!-- Filter Tahun -->
                        <div class="relative w-full sm:w-auto sm:min-w-[120px]">
                            <select name="year" onchange="this.form.submit()" class="w-full appearance-none rounded-lg border-gray-200 bg-gray-50 pl-3 pr-8 py-2 text-sm font-bold text-gray-600 focus:border-emerald-500 focus:ring-emerald-500 transition-all cursor-pointer">
                                <option value="">Semua Waktu</option>
                                @foreach ($years ?? [] as $y)
                                    <option value="{{ $y }}" @selected((string) $year === (string) $y)>Tahun {{ $y }}</option>
                                @endforeach
                            </select>

                        </div>

                        <div class="relative w-full sm:w-auto sm:min-w-[190px]">
                            <select name="period_id" onchange="this.form.submit()" class="w-full appearance-none rounded-lg border-gray-200 bg-gray-50 pl-3 pr-8 py-2 text-sm font-bold text-gray-600 focus:border-emerald-500 focus:ring-emerald-500 transition-all cursor-pointer">
                                <option value="">Semua Periode</option>
                                @foreach ($periods ?? [] as $period)
                                    <option value="{{ $period->id }}" @selected((string) ($periodId ?? '') === (string) $period->id)>
                                        {{ $period->display_label }}{{ $period->sequence > 1 ? ' #' . $period->sequence : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Filter Bentuk Zakat -->
                        <div class="relative w-full sm:w-auto sm:min-w-[140px]">
                            <select name="metode" onchange="this.form.submit()" class="w-full appearance-none rounded-lg border-gray-200 bg-gray-50 pl-3 pr-8 py-2 text-sm font-bold text-gray-600 focus:border-emerald-500 focus:ring-emerald-500 transition-all cursor-pointer">
                                <option value="">Semua Bentuk</option>
                                @foreach ($methods ?? [] as $m)
                                    <option value="{{ $m }}" @selected((string) $metode === (string) $m)>{{ \App\Models\ZakatTransaction::METHOD_LABELS[$m] ?? strtoupper($m) }}</option>
                                @endforeach
                            </select>

                        </div>

                        @if(request('days')) <input type="hidden" name="days" value="{{ request('days') }}"> @endif

                        @if($year || $periodId || $metode)
                            <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-500 transition-all hover:border-emerald-200 hover:text-emerald-700" title="Reset Filters">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                Reset
                            </a>
                        @endif
                    </form>
                </div>
                <div class="p-0 overflow-x-auto w-full">
                    @include('internal.dashboard._rekap_table')
                </div>
            </div>

            <!-- Latest Transactions Section -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-50 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-1.5 h-5 sm:w-2 sm:h-6 bg-blue-500 rounded-full"></div>
                        <h3 class="font-bold text-sm sm:text-base text-gray-800">10 Transaksi Terakhir</h3>
                    </div>
                    <a href="{{ route('internal.transactions.index', array_filter(['year' => $chartYear ?? $activeYear, 'period_id' => $periodId ?? null])) }}" class="text-xs sm:text-sm font-bold text-blue-600 hover:text-blue-700 transition-colors flex items-center gap-1">
                        Lengkap
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                </div>
                <div class="p-0 overflow-x-auto">
                    @include('internal.transactions._latest_table')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0"></script>
<script>
(function() {
    const isOffSeason = {{ $offSeason ? 'true' : 'false' }};
    const lineColor   = isOffSeason ? '#d97706' : '#10b981';
    const lineColorSoft = isOffSeason ? 'rgba(217, 119, 6, 0.92)' : 'rgba(16, 185, 129, 0.92)';
    const gradFrom    = isOffSeason ? 'rgba(217, 119, 6, 0.18)' : 'rgba(16, 185, 129, 0.22)';
    const labelColor  = isOffSeason ? '#92400e' : '#047857';

    function initChart() {
        try {
            if (typeof Chart === 'undefined') {
                setTimeout(initChart, 100);
                return;
            }
            if (typeof ChartDataLabels !== 'undefined' && typeof Chart.registry !== 'undefined') {
                try { Chart.register(ChartDataLabels); } catch (_) {}
            }

            const canvas = document.getElementById('dailyTrendChart');
            if (!canvas) return;

            const ctx = canvas.getContext('2d');
            const gradient = ctx.createLinearGradient(0, 0, 0, 300);
            gradient.addColorStop(0, gradFrom);
            gradient.addColorStop(1, 'rgba(0,0,0,0)');

            const chartValues = {!! json_encode($chartData['datasets'][0]['values'] ?? $chartData['values'] ?? []) !!};
            const chartLabels = {!! json_encode($chartData['labels'] ?? []) !!};
            const chartLabel = {!! json_encode($chartData['datasets'][0]['label'] ?? 'Jumlah Transaksi') !!};

            const sumValues = chartValues.reduce((a, b) => a + (Number(b) || 0), 0);
            const maxValues = Math.max(...chartValues, 0);
            const avgValues = chartValues.length ? sumValues / chartValues.length : 0;
            const lastValue = chartValues[chartValues.length - 1] ?? 0;
            const prevValue = chartValues.length > 1 ? chartValues[chartValues.length - 2] ?? 0 : 0;
            const delta = lastValue - prevValue;

            new Chart(ctx, {
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
                        pointBackgroundColor: '#fff',
                        pointBorderColor: lineColor,
                        pointBorderWidth: 2.5,
                        pointRadius: 4.5,
                        pointHoverRadius: 7,
                        datalabels: {
                            display: (ctx) => Number(ctx.dataset.data[ctx.dataIndex]) > 0,
                            align: 'top',
                            anchor: 'end',
                            offset: 6,
                            color: labelColor,
                            backgroundColor: 'rgba(255, 255, 255, 0.92)',
                            borderColor: labelColor,
                            borderWidth: 1,
                            borderRadius: 6,
                            padding: { top: 3, bottom: 3, left: 6, right: 6 },
                            font: { family: "'Plus Jakarta Sans', sans-serif", weight: '700', size: 10 },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    animation: { duration: 900, easing: 'easeOutQuart' },
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
                                    return [
                                        '',
                                        'Periode ini: ' + new Intl.NumberFormat('id-ID').format(sumValues) + ' total',
                                        'Rata-rata: ' + new Intl.NumberFormat('id-ID').format(Math.round(avgValues)) + ' / hari',
                                        'Tertinggi: ' + new Intl.NumberFormat('id-ID').format(maxValues),
                                        'Delta vs kemarin: ' + (delta >= 0 ? '+' : '') + new Intl.NumberFormat('id-ID').format(delta),
                                    ];
                                },
                            },
                        },
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(226, 232, 240, 0.55)', drawBorder: false },
                            ticks: {
                                stepSize: 1,
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
        } catch (e) {
            console.error("Chart Error: ", e);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initChart);
    } else {
        initChart();
    }
})();
</script>
@endpush
