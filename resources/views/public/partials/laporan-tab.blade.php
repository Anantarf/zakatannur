<div x-show="activeTab === 'laporan'"
    x-transition:enter="transition ease-out duration-500"
    x-transition:enter-start="opacity-0 translate-y-4"
    x-transition:enter-end="opacity-100 translate-y-0">
    <div class="flex justify-center mb-3 sm:mb-5">
        <div class="inline-flex items-center gap-3 rounded-2xl border border-white/70 bg-white/[0.85] px-5 py-2.5 shadow-lg shadow-emerald-900/5 ring-1 ring-emerald-900/5 backdrop-blur">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            <span x-text="clock" class="text-sm sm:text-lg font-bold text-slate-900 tabular-nums"></span>
        </div>
    </div>

    <div class="md:hidden space-y-3 mx-1">
        <template x-for="item in items" :key="'card-' + item.category">
            <article class="rounded-2xl border border-white/80 bg-white/90 p-4 shadow-lg shadow-emerald-900/5 ring-1 ring-emerald-900/5 backdrop-blur">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-[11px] font-black uppercase tracking-wide text-emerald-800">Kategori</p>
                        <h3 class="mt-1 text-lg font-black text-slate-900" x-text="formatCat(item.category)"></h3>
                    </div>
                    <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-bold text-slate-700 tabular-nums" x-text="item.total_jiwa.toLocaleString('id-ID') + ' Jiwa'"></span>
                </div>

                <dl class="mt-4 grid grid-cols-2 gap-3">
                    <div class="rounded-xl border border-emerald-100 bg-emerald-50/80 p-3">
                        <dt class="text-[10px] font-black uppercase tracking-wide text-emerald-700/70">Uang</dt>
                        <dd class="mt-1 text-base font-black text-emerald-700 tabular-nums" x-text="'Rp ' + (item.total_uang || 0).toLocaleString('id-ID')"></dd>
                    </div>
                    <div class="rounded-xl border border-amber-100 bg-amber-50/80 p-3">
                        <dt class="text-[10px] font-black uppercase tracking-wide text-amber-700/70">Beras</dt>
                        <dd class="mt-1 text-base font-black text-amber-700 tabular-nums" x-text="(item.total_beras_kg || 0).toFixed(2).replace('.', ',') + ' Kg'"></dd>
                    </div>
                </dl>
            </article>
        </template>

        <div x-show="items.length > 0" class="rounded-2xl border border-emerald-200 bg-emerald-900 p-4 text-white shadow-lg shadow-emerald-900/[0.15]">
            <p class="text-[11px] font-black uppercase tracking-wide text-emerald-100/70">Total Penerimaan</p>
            <div class="mt-3 grid grid-cols-1 gap-2 text-sm font-black tabular-nums">
                <span class="text-emerald-50" x-text="(totals.total_jiwa || 0).toLocaleString('id-ID') + ' Jiwa'"></span>
                <span class="text-emerald-100" x-text="'Rp ' + (totals.total_uang || 0).toLocaleString('id-ID')"></span>
                <span class="text-amber-200" x-text="(totals.total_beras_kg || 0).toFixed(2).replace('.', ',') + ' Kg'"></span>
            </div>
        </div>

        <p x-show="items.length === 0" class="rounded-2xl border border-slate-200 bg-white px-6 py-10 text-center text-sm font-bold text-slate-400">Belum ada data penerimaan masuk tahun ini.</p>
    </div>

    <div class="hidden md:block overflow-x-auto custom-scrollbar rounded-[1.75rem] border border-white/80 shadow-[0_24px_70px_-24px_rgba(6,78,59,0.35)] ring-1 ring-emerald-900/5 mx-1 sm:mx-0 bg-white/95 backdrop-blur">
        <table class="w-full border-collapse bg-white">
            <thead class="sticky top-0 z-20 shadow-md shadow-emerald-900/5">
                <tr class="bg-emerald-900 border-b border-emerald-800">
                    <th class="pl-4 py-4 sm:pl-10 text-left text-[13px] sm:text-[15px] font-bold text-emerald-50 uppercase tracking-wide">Kategori Zakat</th>
                    <th class="px-2 py-4 sm:px-4 text-center text-[13px] sm:text-[15px] font-bold text-emerald-50 uppercase tracking-wide">Jiwa</th>
                    <th class="px-2 py-4 sm:px-4 text-center text-[13px] sm:text-[15px] font-bold text-emerald-50 uppercase tracking-wide">Total Uang</th>
                    <th class="px-2 py-4 sm:px-4 text-center text-[13px] sm:text-[15px] font-bold text-emerald-50 uppercase tracking-wide">Total Beras</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                <template x-for="(item, index) in items" :key="item.category">
                    <tr :class="index % 2 !== 0 ? 'bg-emerald-50/30' : 'bg-white'" class="transition-colors hover:bg-emerald-50/60">
                        <td class="pl-4 py-5 sm:pl-10 text-base sm:text-xl font-bold text-slate-900 uppercase">
                            <span class="whitespace-nowrap" x-text="formatCat(item.category)"></span>
                        </td>
                        <td class="px-2 py-5 sm:px-4 text-center text-[15px] sm:text-2xl text-slate-900 font-bold tabular-nums" x-text="item.total_jiwa.toLocaleString('id-ID') + ' Jiwa'"></td>
                        <td class="px-2 py-5 sm:px-4 text-center text-[13px] sm:text-2xl text-emerald-600 font-bold tabular-nums" x-text="'Rp ' + (item.total_uang || 0).toLocaleString('id-ID')"></td>
                        <td class="px-2 py-5 sm:px-4 text-center text-[13px] sm:text-2xl text-amber-600 font-bold tabular-nums" x-text="(item.total_beras_kg || 0).toFixed(2).replace('.', ',') + ' Kg'"></td>
                    </tr>
                </template>
                <template x-if="items.length === 0">
                    <tr>
                        <td colspan="4" class="px-8 py-12 text-base text-slate-400 text-center font-medium">Belum ada data penerimaan masuk tahun ini.</td>
                    </tr>
                </template>
            </tbody>
            <tfoot x-show="items.length > 0" class="border-t-4 border-emerald-600/20">
                <tr class="bg-emerald-950 text-center">
                    <td class="pl-4 py-4 sm:pl-10 text-[13px] sm:text-[18px] font-bold tracking-wide uppercase text-emerald-100 text-left">TOTAL</td>
                    <td class="px-2 py-4 sm:px-4 text-slate-900 text-[15px] sm:text-2xl font-bold tabular-nums whitespace-nowrap">
                        <span id="totalJiwa" class="text-emerald-50" x-text="(totals.total_jiwa || 0).toLocaleString('id-ID') + ' Jiwa'"></span>
                    </td>
                    <td class="px-2 py-4 sm:px-4 text-emerald-600 text-[15px] sm:text-2xl font-bold tabular-nums whitespace-nowrap">
                        <span id="totalUang" class="text-emerald-100" x-text="'Rp ' + (totals.total_uang || 0).toLocaleString('id-ID')"></span>
                    </td>
                    <td class="px-2 py-4 sm:px-4 text-amber-600 text-[15px] sm:text-2xl font-bold tabular-nums whitespace-nowrap">
                        <span id="totalBeras" class="text-amber-200" x-text="(totals.total_beras_kg || 0).toFixed(2).replace('.', ',') + ' Kg'"></span>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>

    <p x-show="error" x-text="error" class="mt-4 rounded-xl border border-red-100 bg-red-50/50 p-3 text-[10px] font-bold text-red-500 text-center" role="alert"></p>
</div>
