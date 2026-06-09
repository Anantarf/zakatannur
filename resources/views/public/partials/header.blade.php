<header class="mx-auto w-full max-w-6xl">
    <div class="public-header-shell">
        <div class="grid grid-cols-1 items-stretch gap-4 lg:grid-cols-2">
            <div class="flex flex-col justify-center">
                <p class="public-header-kicker">Portal Transparansi Zakat</p>
                <h1 class="public-header-title">Masjid An-Nur</h1>
                <div class="public-header-meta">
                    <span class="public-header-location">Komplek BPK V Gandul</span>
                    <span class="public-header-divider"></span>
                    <span class="text-neutral-500">Laporan penerimaan zakat real-time yang transparan dan akuntabel.</span>
                </div>
            </div>

            <div class="flex flex-col gap-3">
                <div class="public-clock-pill">
                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                    <span x-text="clock" class="public-clock-text"></span>
                </div>

                <div class="grid grid-cols-3 gap-3 sm:gap-4">
                    <div class="public-stat-card">
                        <p class="public-stat-label">Jiwa</p>
                        <p class="public-stat-value" x-text="formatJiwaPlain(totals.total_jiwa)"></p>
                    </div>
                    <div class="public-stat-card">
                        <p class="public-stat-label">Uang</p>
                        <p class="public-stat-value" x-text="formatUang(totals.total_uang)"></p>
                    </div>
                    <div class="public-stat-card">
                        <p class="public-stat-label">Beras</p>
                        <p class="public-stat-value" x-text="formatBeras(totals.total_beras_kg)"></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
