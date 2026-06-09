<header class="mx-auto w-full max-w-6xl"
    :class="activeTab === 'laporan' ? 'pb-1.5 sm:pb-2' : 'pb-2 sm:pb-2.5'">
    <div class="public-header-shell">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div class="max-w-xl">
                <p class="public-header-kicker">
                    Portal Transparansi Zakat
                </p>
                <h1 class="public-header-title">
                    Masjid An-Nur
                </h1>
                <div class="public-header-meta">
                    <span class="tracking-[0.04em]">Komplek BPK V Gandul</span>
                    <span class="public-header-divider"></span>
                    <span class="text-neutral-500">Laporan penerimaan zakat real-time yang transparan dan akuntabel.</span>
                </div>
            </div>

            <div class="flex flex-col gap-2.5 lg:items-end">
                <div class="public-pill public-pill-brand">
                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                    <span x-text="clock" class="text-[11px] font-semibold text-neutral-800 tabular-nums sm:text-[12px]"></span>
                </div>

                <div class="grid grid-cols-3 gap-2 sm:min-w-[28rem]">
                    <div class="public-metric-card">
                        <p class="public-metric-label">Jiwa</p>
                        <p class="public-metric-value" x-text="(totals.total_jiwa || 0).toLocaleString('id-ID')"></p>
                    </div>
                    <div class="public-metric-card-brand">
                        <p class="public-metric-label public-metric-label-brand">Uang</p>
                        <p class="public-metric-value public-metric-value-brand" x-text="'Rp ' + (totals.total_uang || 0).toLocaleString('id-ID')"></p>
                    </div>
                    <div class="public-metric-card-amber">
                        <p class="public-metric-label public-metric-label-amber">Beras</p>
                        <p class="public-metric-value public-metric-value-amber" x-text="(totals.total_beras_kg || 0).toFixed(2).replace('.', ',') + ' Kg'"></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
