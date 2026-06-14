<div x-show="activeTab === 'laporan'"
    x-transition:enter="transition ease-out duration-500"
    x-transition:enter-start="opacity-0 translate-y-4"
    x-transition:enter-end="opacity-100 translate-y-0">
    <section class="public-report-shell">
        <div class="flex items-center justify-between border-b border-slate-200/75 pb-3">
            <div class="flex items-center gap-3">
                <span class="h-9 w-1 rounded-full bg-brand-500"></span>
                <h2 class="text-[1.22rem] font-bold leading-tight tracking-[-0.01em] text-slate-950 sm:text-[1.35rem]">Ringkasan penerimaan</h2>
            </div>
        </div>

        <div x-show="items.length > 0" class="mt-4 md:hidden">
            <div class="space-y-2.5">
                <template x-for="item in items" :key="'card-' + item.category">
                    <article class="public-report-mobile-row">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-[1.02rem] font-bold text-slate-950" x-text="formatCat(item.category)"></p>
                                <span class="mt-1 inline-flex rounded-full border border-sky-100 bg-sky-50 px-2.5 py-1 text-[11px] font-semibold text-sky-900 tabular-nums" x-text="formatJiwa(item.total_jiwa)"></span>
                            </div>
                        </div>
                        <dl class="mt-3 grid grid-cols-2 gap-2">
                            <div class="public-report-value-cell public-report-value-brand">
                                <dt>Uang</dt>
                                <dd x-text="formatUang(item.total_uang)"></dd>
                            </div>
                            <div class="public-report-value-cell public-report-value-amber">
                                <dt>Beras</dt>
                                <dd x-text="formatBeras(item.total_beras_kg)"></dd>
                            </div>
                        </dl>
                    </article>
                </template>
                <article class="public-report-mobile-total">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-sm font-semibold uppercase tracking-[0.12em] text-slate-500">Total</p>
                        <span class="rounded-full border border-sky-100 bg-sky-50 px-2.5 py-1 text-[11px] font-semibold text-sky-900 tabular-nums" x-text="formatJiwa(totals.total_jiwa)"></span>
                    </div>
                    <div class="mt-3 grid grid-cols-2 gap-2">
                        <div class="text-right">
                            <p class="text-[10px] font-semibold uppercase tracking-[0.1em] text-brand-700">Uang</p>
                            <p class="mt-1 text-sm font-semibold text-brand-800 tabular-nums" x-text="formatUang(totals.total_uang)"></p>
                        </div>
                        <div class="text-right">
                            <p class="text-[10px] font-semibold uppercase tracking-[0.1em] text-amber-700">Beras</p>
                            <p class="mt-1 text-sm font-semibold text-amber-800 tabular-nums" x-text="formatBeras(totals.total_beras_kg)"></p>
                        </div>
                    </div>
                </article>
            </div>
        </div>

        <div x-show="items.length > 0" class="mt-4 hidden overflow-hidden rounded-[1.1rem] border border-slate-200/80 bg-white md:block">
            <div class="grid grid-cols-[1.25fr_0.7fr_1fr_1fr] border-b border-slate-100 bg-slate-50 px-5 py-3 text-[11px] font-bold uppercase tracking-[0.09em] text-slate-400">
                <span>Kategori</span>
                <span class="text-right">Jiwa</span>
                <span class="text-right">Rp</span>
                <span class="text-right">Kg</span>
            </div>

            <div class="divide-y divide-slate-100">
                <template x-for="item in items" :key="'summary-' + item.category">
                    <article class="public-report-table-row grid-cols-[1.25fr_0.7fr_1fr_1fr]">
                        <p class="truncate text-[0.98rem] font-bold text-slate-950" x-text="formatCat(item.category)"></p>
                        <p class="text-right text-[0.98rem] font-semibold text-sky-950 tabular-nums" x-text="formatJiwaPlain(item.total_jiwa)"></p>
                        <p class="text-right text-[0.98rem] font-semibold text-brand-800 tabular-nums" x-text="formatUang(item.total_uang)"></p>
                        <p class="text-right text-[0.98rem] font-semibold text-amber-800 tabular-nums" x-text="formatBeras(item.total_beras_kg)"></p>
                    </article>
                </template>
                <article class="grid grid-cols-[1.25fr_0.7fr_1fr_1fr] items-center gap-2.5 bg-slate-50 px-5 py-3.5">
                    <p class="text-sm font-bold uppercase tracking-[0.08em] text-slate-800">Total</p>
                    <p class="text-right text-[1rem] font-bold text-sky-950 tabular-nums" x-text="formatJiwaPlain(totals.total_jiwa)"></p>
                    <p class="text-right text-[1rem] font-bold text-brand-800 tabular-nums" x-text="formatUang(totals.total_uang)"></p>
                    <p class="text-right text-[1rem] font-bold text-amber-800 tabular-nums" x-text="formatBeras(totals.total_beras_kg)"></p>
                </article>
            </div>
        </div>

        <p x-show="items.length === 0" class="px-6 py-10 text-center text-sm font-semibold text-slate-600">Belum ada data penerimaan masuk tahun ini.</p>
    </section>

    <p x-show="error" x-text="error" class="mt-4 rounded-xl border border-red-100 bg-red-50/50 p-3 text-center text-[10px] font-bold text-red-500" role="alert"></p>
</div>
