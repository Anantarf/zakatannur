<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <h2 class="font-bold text-xl sm:text-2xl text-brand-800 leading-tight flex items-center justify-center sm:justify-start gap-2 text-center sm:text-left">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-brand-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
                Pusat Pengelolaan Zakat
            </h2>
            <div class="hidden sm:block"></div>
        </div>
    </x-slot>

    <div class="py-4 sm:py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4 sm:space-y-5">
            <div class="grid grid-cols-1 gap-4 xl:grid-cols-[minmax(0,1.2fr)_minmax(300px,0.8fr)]">
                <div class="rounded-card border border-slate-200 bg-white p-5 shadow-sm ring-1 ring-slate-200/60 sm:p-6">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div class="space-y-2">
                            <p class="ui-kicker">Ringkasan</p>
                            <h3 class="ui-section-title text-xl sm:text-2xl">Manajemen Operasional Zakat</h3>
                            <p class="ui-body-muted max-w-2xl">Pantau penerimaan zakat dan kelola operasional harian.</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <a href="{{ route('internal.transactions.create') }}" class="ui-btn ui-btn-primary w-full sm:w-auto">Catat Transaksi</a>
                            <a href="{{ route('internal.transactions.index', array_filter(['year' => $year, 'period_id' => $periodId, 'metode' => $metode])) }}" class="ui-btn ui-btn-secondary w-full sm:w-auto">Buka Riwayat</a>
                        </div>
                    </div>

                    @if (($workspace['warning_groups'] ?? 0) > 0)
                        <div class="mt-5 flex flex-col gap-3 rounded-2xl border border-amber-200 bg-amber-50/85 p-4 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex items-start gap-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-5 w-5 shrink-0 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.25" d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" />
                                </svg>
                                <div>
                                    <p class="text-sm font-bold text-amber-950">{{ number_format($workspace['warning_groups'], 0, ',', '.') }} kelompok transaksi perlu review</p>
                                    <p class="mt-0.5 text-xs leading-5 text-amber-800">Tinjau anomali aktif agar data operasional tetap bersih.</p>
                                </div>
                            </div>
                            <a href="{{ route('internal.anomalies.index', ['review_status' => \App\Models\TransactionRiskReview::REVIEW_BELUM_DITINJAU]) }}" class="ui-btn shrink-0 border border-amber-300 bg-amber-100 px-4 py-2 text-sm text-amber-900 hover:bg-amber-200 focus:ring-amber-400">
                                Review Sekarang
                            </a>
                        </div>
                    @endif

                    <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <p class="ui-label">Total Transaksi</p>
                            <p class="ui-metric-value mt-2 text-3xl text-slate-900">{{ number_format($payload['totals']['jumlah_transaksi'] ?? 0, 0, ',', '.') }}</p>
                            <p class="ui-body-muted mt-2 text-[11px] leading-5">Total transaksi dalam filter aktif.</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <p class="ui-label">Transaksi Hari Ini</p>
                            <p class="ui-metric-value mt-2 text-3xl text-brand-500">{{ number_format($workspace['today_count'] ?? 0, 0, ',', '.') }}</p>
                            <p class="ui-body-muted mt-2 text-[11px] leading-5">Transaksi valid hari ini.</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <p class="ui-label">Transaksi Terakhir</p>
                            <p class="ui-metric-value mt-2 text-xl text-slate-900">
                                {{ ($workspace['latest_transaction_at'] ?? null)?->format('d/m/Y H:i') ?? '-' }}
                            </p>
                            <p class="ui-body-muted mt-2 text-[11px] leading-5">Waktu catat terbaru.</p>
                        </div>
                    </div>
                </div>

                <div class="ui-card p-5 sm:p-6">
                    <p class="ui-label text-slate-400">Navigasi Cepat</p>
                    <div class="mt-4 grid grid-cols-1 gap-3">
                        <a href="{{ route('internal.transactions.index', array_filter(['year' => $year, 'period_id' => $periodId, 'metode' => $metode])) }}" class="ui-action-tile">
                            <p class="text-sm font-bold text-slate-900">Riwayat Transaksi</p>
                            <p class="mt-1 text-sm leading-6 text-slate-600">Telusuri transaksi lengkap sesuai filter dashboard saat ini.</p>
                        </a>
                        <a href="{{ route('internal.muzakki.index') }}" class="ui-action-tile ui-action-tile-accent">
                            <p class="text-sm font-bold text-brand-900">Data Muzakki</p>
                            <p class="mt-1 text-sm leading-6 text-brand-800">Cari data muzakki dan lihat riwayat yang sudah tercatat.</p>
                        </a>
                        <a href="{{ route('internal.transactions.trash') }}" class="ui-action-tile ui-action-tile-info">
                            <p class="text-sm font-bold text-blue-900">Trash Transaksi</p>
                            <p class="mt-1 text-sm leading-6 text-blue-800">Cek transaksi yang pernah dihapus dan masih tersimpan di trash.</p>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Daily Trend Chart -->
            <div class="ui-card overflow-hidden !p-0">
                <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-slate-50 flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4">
                    <div class="flex items-center gap-2 flex-wrap">
                        <div class="w-1.5 h-5 sm:w-2 sm:h-6 bg-brand-500 rounded-full flex-shrink-0"></div>
                        <h3 class="ui-section-title text-sm sm:text-base">Grafik Penerimaan</h3>
                        @if($chartPeriodLabel)
                            <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 border border-amber-200 px-2.5 py-0.5 text-[10px] font-semibold text-amber-700 uppercase tracking-[0.08em] whitespace-nowrap">
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

                        <x-ui-select-custom name="days" :value="$activeDays" :options="[
                            '7' => '7 Hari',
                            '14' => '14 Hari',
                            '30' => '30 Hari',
                        ]" @change="$el.closest('form').submit()" class="w-28 sm:w-32" />
                    </form>
                </div>
                <div class="px-4 pb-4 pt-3 sm:px-6 sm:pb-6 sm:pt-4">
                    @if (!empty($dashboardChartSourceNote))
                        <div class="mb-3 rounded-xl border border-slate-100 bg-slate-50 px-4 py-2.5 text-xs leading-relaxed text-slate-600">
                            {{ $dashboardChartSourceNote }}
                        </div>
                    @endif
                    @if (!empty($dashboardChartRange['fallback_note']))
                        <div class="mb-3 rounded-xl border border-amber-100 bg-amber-50 px-4 py-2.5 text-xs leading-relaxed text-amber-800">
                            {{ $dashboardChartRange['fallback_note'] }}
                        </div>
                    @endif
                    <div 
                        x-data="dailyTrendChart({
                            chartValues: {{ json_encode($chartData['datasets'][0]['values'] ?? $chartData['values'] ?? []) }},
                            chartLabels: {{ json_encode($chartData['labels'] ?? []) }},
                            chartLabel: '{{ $chartData['datasets'][0]['label'] ?? 'Jumlah Transaksi' }}',
                            isOffSeason: {{ $offSeason ? 'true' : 'false' }}
                        })"
                        class="relative w-full flex items-center justify-center bg-slate-50 rounded-xl" style="height: 320px; min-height: 320px;" id="chart-container">
                        <div x-show="statusText" x-text="statusText" class="absolute inset-0 flex items-center justify-center text-slate-400 font-medium text-sm">Sedang memuat grafik...</div>

                        <canvas x-ref="canvas" class="absolute inset-0 w-full h-full z-10"></canvas>
                    </div>
                </div>
            </div>

            <!-- Rekap Table Section -->
            <div class="ui-card overflow-hidden !p-0">
                <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-slate-50 flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4">
                    <div class="flex items-center gap-2">
                        <div class="w-1.5 h-5 sm:w-2 sm:h-6 bg-brand-500 rounded-full"></div>
                        <h3 class="ui-section-title text-sm sm:text-base">Rekapitulasi Zakat</h3>
                    </div>

                    <form method="GET" action="{{ route('dashboard') }}" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 w-full sm:w-auto mt-2 sm:mt-0">
                        <!-- Filter Tahun -->
                        <div class="relative w-full sm:w-auto sm:min-w-[120px]">
                            @php
                                $yearOptions = ['' => 'Semua Waktu'];
                                foreach ($years ?? [] as $y) {
                                    $yearOptions[$y] = 'Tahun ' . $y;
                                }
                            @endphp
                            <x-ui-select-custom name="year" :value="$year" :options="$yearOptions" @change="$el.closest('form').submit()" />

                        </div>

                        <div class="relative w-full sm:w-auto sm:min-w-[190px]">
                            @php
                                $periodOptions = ['' => 'Semua Periode'];
                                foreach ($periods ?? [] as $period) {
                                    $periodOptions[$period->id] = $period->display_label . ($period->sequence > 1 ? ' #' . $period->sequence : '');
                                }
                            @endphp
                            <x-ui-select-custom name="period_id" :value="$periodId" :options="$periodOptions" @change="$el.closest('form').submit()" />
                        </div>

                        <!-- Filter Bentuk Zakat -->
                        <div class="relative w-full sm:w-auto sm:min-w-[140px]">
                            @php
                                $methodOptions = ['' => 'Semua Bentuk'];
                                foreach ($methods ?? [] as $m) {
                                    $methodOptions[$m] = \App\Models\ZakatTransaction::METHOD_LABELS[$m] ?? strtoupper($m);
                                }
                            @endphp
                            <x-ui-select-custom name="metode" :value="$metode" :options="$methodOptions" @change="$el.closest('form').submit()" />

                        </div>

                        @if(request('days')) <input type="hidden" name="days" value="{{ request('days') }}"> @endif

                        @if($year || $periodId || $metode)
                            <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-500 transition-all hover:border-brand-200 hover:text-brand-700" title="Reset Filters">
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
            <div class="ui-card overflow-hidden !p-0">
                <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-slate-50 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-1.5 h-5 sm:w-2 sm:h-6 bg-brand-500 rounded-full"></div>
                        <h3 class="ui-section-title text-sm sm:text-base">10 Transaksi Terakhir</h3>
                    </div>
                    <a href="{{ route('internal.transactions.index', array_filter(['year' => $chartYear ?? $activeYear, 'period_id' => $periodId ?? null])) }}" class="text-xs sm:text-sm font-bold text-brand-600 hover:text-brand-700 transition-colors flex items-center gap-1">
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


