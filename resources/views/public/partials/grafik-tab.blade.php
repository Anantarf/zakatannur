<div x-show="activeTab === 'grafik'"
    x-transition:enter="transition ease-out duration-500"
    x-transition:enter-start="opacity-0 translate-y-4"
    x-transition:enter-end="opacity-100 translate-y-0"
    class="p-1 sm:p-4 flex flex-col items-center justify-center">
    <div class="w-full max-w-6xl relative bg-white/90 rounded-[1.75rem] border border-white/80 p-3 sm:px-6 sm:py-5 shadow-xl shadow-emerald-900/5 ring-1 ring-emerald-900/5 backdrop-blur mb-1">
        <div class="flex items-center justify-between mb-3">
            <div class="flex items-center gap-2">
                <div class="p-1.5 bg-emerald-800 rounded-xl text-white shadow-md">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
                </div>
                <h2 class="text-lg sm:text-xl font-black text-slate-900">Grafik Penerimaan Harian</h2>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-3 mb-4">
            <div class="bg-emerald-50 py-4 px-5 sm:px-6 rounded-2xl text-emerald-950 border border-emerald-100 shadow-lg shadow-emerald-900/5 relative overflow-hidden group">
                <div class="absolute -right-4 -top-4 opacity-10 group-hover:scale-110 transition duration-700 text-emerald-700">
                    <svg class="h-24 w-24" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1.41 16.09V20h-2.18v-1.93c-1.39-.14-2.81-.72-3.79-1.6l1.24-1.54c.83.69 1.95 1.18 2.85 1.3.75.1 1.25-.13 1.25-.66 0-.48-.52-.77-1.57-1.1-1.63-.5-3.69-1.35-3.69-3.75 0-1.89 1.24-3.41 3.12-3.86V5h2.18v1.89c1.23.11 2.3.61 3.03 1.22l-1.14 1.58c-.59-.44-1.37-.8-2.14-.85-.75-.05-1.17.21-1.17.61 0 .42.48.66 1.7 1.1 1.79.64 3.56 1.55 3.56 3.82 0 2.21-1.59 3.49-3.72 3.82z"/></svg>
                </div>
                <p class="text-[10px] font-black uppercase tracking-wide text-emerald-700/75 mb-0.5">Total Penerimaan Uang</p>
                <h3 class="text-xl sm:text-2xl font-black truncate font-mono text-emerald-800" id="live-total-uang" x-text="'Rp ' + (totals.total_uang || 0).toLocaleString('id-ID')">Rp 0</h3>
            </div>
            <div class="bg-amber-50 py-4 px-5 sm:px-6 rounded-2xl text-amber-950 border border-amber-100 shadow-lg shadow-amber-900/5 relative overflow-hidden group">
                <div class="absolute -right-4 -top-4 opacity-10 group-hover:scale-110 transition duration-700 text-amber-700">
                    <svg class="h-24 w-24" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M5 7h14l-1.5 15h-11L5 7z"/>
                        <path d="M5 7L3 3l5 3h8l5-3-2 4H5z" opacity="0.8"/>
                        <rect x="9" y="12" width="6" height="4" opacity="0.3"/>
                    </svg>
                </div>
                <p class="text-[10px] font-black uppercase tracking-wide text-amber-700/75 mb-0.5">Total Penerimaan Beras</p>
                <h3 class="text-xl sm:text-2xl font-black truncate font-mono text-amber-800" id="live-total-beras" x-text="(totals.total_beras_kg || 0).toFixed(2).replace('.', ',') + ' Kg'">0 Kg</h3>
            </div>
        </div>

        <div class="h-[280px] sm:h-[450px] w-full relative">
            <canvas id="dailyChart"></canvas>
            <div id="idle-cards-container" class="absolute inset-0 pointer-events-none overflow-hidden mt-8"></div>
        </div>
    </div>
</div>
