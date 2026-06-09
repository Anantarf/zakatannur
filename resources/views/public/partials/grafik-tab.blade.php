<div x-show="activeTab === 'grafik'"
    x-transition:enter="transition ease-out duration-500"
    x-transition:enter-start="opacity-0 translate-y-4"
    x-transition:enter-end="opacity-100 translate-y-0"
    class="flex flex-col items-center justify-center p-1 sm:p-2">
    <div class="public-chart-shell sm:px-5 sm:py-4">
        <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
            <div class="flex items-center gap-2">
                <div class="public-chart-icon">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
                </div>
                <div>
                    <h2 class="text-[1rem] sm:text-[1.08rem] font-semibold text-neutral-900">Grafik penerimaan harian</h2>
                    <p class="hidden text-[11px] text-neutral-500 sm:block">Pola harian sebagai insight pendukung setelah ringkasan utama.</p>
                </div>
            </div>

            <div class="public-chart-filter" role="tablist" aria-label="Filter grafik">
                <button type="button" role="tab"
                    :aria-selected="chartFilter === 'uang'"
                    :class="chartFilter === 'uang' ? 'public-chart-filter-active' : 'public-chart-filter-inactive'"
                    @click="setChartFilter('uang')">Uang</button>
                <button type="button" role="tab"
                    :aria-selected="chartFilter === 'beras'"
                    :class="chartFilter === 'beras' ? 'public-chart-filter-active' : 'public-chart-filter-inactive'"
                    @click="setChartFilter('beras')">Beras</button>
                <button type="button" role="tab"
                    :aria-selected="chartFilter === 'semua'"
                    :class="chartFilter === 'semua' ? 'public-chart-filter-active' : 'public-chart-filter-inactive'"
                    @click="setChartFilter('semua')">Semua</button>
            </div>

            <span class="public-pill public-pill-brand hidden px-3 py-1 text-[10px] tracking-[0.08em] sm:inline-flex" x-text="dailyChartData.range?.label || ''"></span>
        </div>

        <div class="mb-2.5 grid grid-cols-1 gap-2.5 sm:grid-cols-2">
            <div class="public-chart-metric public-chart-metric-brand">
                <div class="absolute -right-4 -top-4 opacity-10 group-hover:scale-110 transition duration-700 text-emerald-700">
                    <svg class="h-24 w-24" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1.41 16.09V20h-2.18v-1.93c-1.39-.14-2.81-.72-3.79-1.6l1.24-1.54c.83.69 1.95 1.18 2.85 1.3.75.1 1.25-.13 1.25-.66 0-.48-.52-.77-1.57-1.1-1.63-.5-3.69-1.35-3.69-3.75 0-1.89 1.24-3.41 3.12-3.86V5h2.18v1.89c1.23.11 2.3.61 3.03 1.22l-1.14 1.58c-.59-.44-1.37-.8-2.14-.85-.75-.05-1.17.21-1.17.61 0 .42.48.66 1.7 1.1 1.79.64 3.56 1.55 3.56 3.82 0 2.21-1.59 3.49-3.72 3.82z"/></svg>
                </div>
                <p class="mb-0.5 text-[10px] font-medium tracking-[0.08em] text-emerald-700/75">Total penerimaan uang</p>
                <h3 class="text-[1.02rem] sm:text-[1.2rem] font-semibold text-emerald-800 tabular-nums" id="live-total-uang" x-text="formatUang(totals.total_uang)">Rp 0</h3>
            </div>
            <div class="public-chart-metric public-chart-metric-amber">
                <div class="absolute -right-4 -top-4 opacity-10 group-hover:scale-110 transition duration-700 text-amber-700">
                    <svg class="h-24 w-24" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M5 7h14l-1.5 15h-11L5 7z"/>
                        <path d="M5 7L3 3l5 3h8l5-3-2 4H5z" opacity="0.8"/>
                        <rect x="9" y="12" width="6" height="4" opacity="0.3"/>
                    </svg>
                </div>
                <p class="mb-0.5 text-[10px] font-medium tracking-[0.08em] text-amber-700/75">Total penerimaan beras</p>
                <h3 class="text-[1.02rem] sm:text-[1.2rem] font-semibold text-amber-800 tabular-nums" id="live-total-beras" x-text="formatBeras(totals.total_beras_kg)">0 Kg</h3>
            </div>
        </div>

        <div class="space-y-3">
            <div x-show="chartFilter !== 'beras'" x-transition.opacity.duration.300ms
                class="public-chart-card">
                <div class="public-chart-card-head">
                    <span class="public-chart-card-title">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                        Grafik Penerimaan Uang
                    </span>
                    <span class="public-chart-card-meta" id="chart-uang-range"></span>
                </div>
                <div class="h-[240px] w-full sm:h-[280px] relative">
                    <canvas id="dailyChartUang"></canvas>
                </div>
            </div>

            <div x-show="chartFilter !== 'uang'" x-transition.opacity.duration.300ms
                class="public-chart-card">
                <div class="public-chart-card-head">
                    <span class="public-chart-card-title">
                        <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                        Grafik Penerimaan Beras
                    </span>
                    <span class="public-chart-card-meta" id="chart-beras-range"></span>
                </div>
                <div class="h-[240px] w-full sm:h-[280px] relative">
                    <canvas id="dailyChartBeras"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>
