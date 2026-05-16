<div x-show="activeTab === 'laporan'"
    x-transition:enter="transition ease-out duration-500"
    x-transition:enter-start="opacity-0 translate-y-4"
    x-transition:enter-end="opacity-100 translate-y-0">
    <div class="flex justify-center mb-1 sm:mb-3">
        <div class="inline-flex items-center gap-3 px-5 py-2.5 rounded-2xl bg-white border border-emerald-500/10 shadow-xl shadow-emerald-900/5 ring-1 ring-emerald-500/5">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            <span x-text="clock" class="text-sm sm:text-lg font-bold text-slate-900 tracking-tight tabular-nums"></span>
        </div>
    </div>

    <div class="overflow-x-auto custom-scrollbar rounded-2xl border border-slate-300/50 shadow-[0_30px_80px_-15px_rgba(6,78,59,0.2)] ring-1 ring-emerald-500/5 mx-1 sm:mx-0 bg-white">
        <table class="w-full border-collapse bg-white">
            <thead class="sticky top-0 z-20 shadow-md shadow-emerald-900/5">
                <tr class="bg-emerald-600 border-b border-emerald-700">
                    <th class="pl-4 py-4 sm:pl-10 text-left text-[13px] sm:text-[16px] font-bold text-white uppercase tracking-[0.2em]">Kategori Zakat</th>
                    <th class="px-2 py-4 sm:px-4 text-center text-[13px] sm:text-[16px] font-bold text-white uppercase tracking-[0.2em]">Jiwa</th>
                    <th class="px-2 py-4 sm:px-4 text-center text-[13px] sm:text-[16px] font-bold text-white uppercase tracking-[0.2em]">Total Uang</th>
                    <th class="px-2 py-4 sm:px-4 text-center text-[13px] sm:text-[16px] font-bold text-white uppercase tracking-[0.2em]">Total Beras</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                <template x-for="(item, index) in items" :key="item.category">
                    <tr :class="index % 2 !== 0 ? 'bg-slate-50/50' : 'bg-white'" class="transition-colors">
                        <td class="pl-4 py-5 sm:pl-10 text-base sm:text-xl font-bold text-slate-900 uppercase tracking-tighter sm:tracking-normal">
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
                <tr class="bg-emerald-50 text-center">
                    <td class="pl-4 py-4 sm:pl-10 text-[13px] sm:text-[18px] font-bold tracking-widest uppercase text-emerald-800/60 text-left">TOTAL</td>
                    <td class="px-2 py-4 sm:px-4 text-slate-900 text-[15px] sm:text-2xl font-bold tabular-nums whitespace-nowrap">
                        <span id="totalJiwa" x-text="(totals.total_jiwa || 0).toLocaleString('id-ID') + ' Jiwa'"></span>
                    </td>
                    <td class="px-2 py-4 sm:px-4 text-emerald-600 text-[15px] sm:text-2xl font-bold tabular-nums whitespace-nowrap">
                        <span id="totalUang" x-text="'Rp ' + (totals.total_uang || 0).toLocaleString('id-ID')"></span>
                    </td>
                    <td class="px-2 py-4 sm:px-4 text-amber-600 text-[15px] sm:text-2xl font-bold tabular-nums whitespace-nowrap">
                        <span id="totalBeras" x-text="(totals.total_beras_kg || 0).toFixed(2).replace('.', ',') + ' Kg'"></span>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>

    <p x-show="error" x-html="error" class="mt-4 rounded-xl border border-red-100 bg-red-50/50 p-3 text-[10px] font-bold text-red-500 text-center" role="alert"></p>
</div>
