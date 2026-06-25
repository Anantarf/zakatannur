<div x-show="activeTab === 'laporan'"
    x-transition:enter="transition ease-out duration-500"
    x-transition:enter-start="opacity-0 translate-y-4"
    x-transition:enter-end="opacity-100 translate-y-0">
    <section class="public-report-shell">
        <div class="flex items-center justify-between border-b border-[#d7e5df] pb-3">
            <div class="flex items-center gap-3">
                <span class="h-9 w-1 rounded-full bg-brand-500"></span>
                <div>
                    <h2 class="text-lg font-bold leading-relaxed tracking-[-0.01em] text-slate-950">Ringkasan penerimaan</h2>
                </div>
            </div>
        </div>

        <template x-if="isLoading">
            <div class="space-y-2 mt-4 md:hidden">
                <div class="h-12 bg-slate-100 rounded-xl animate-pulse"></div>
                <div class="h-24 bg-slate-100 rounded-xl animate-pulse"></div>
                <div class="h-24 bg-slate-100 rounded-xl animate-pulse"></div>
            </div>
        </template>

        <div x-show="!isLoading && items.length > 0" class="mt-4 md:hidden" role="region" aria-label="Ringkasan penerimaan zakat (mobile)">
            <div class="space-y-2.5">
                <template x-for="item in items" :key="'card-' + item.category">
                    <article class="public-report-mobile-row">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h3 class="truncate text-sm font-bold text-slate-950 leading-relaxed" x-text="formatCat(item.category)"></h3>
                                <span class="mt-1 min-h-9 items-center inline-flex public-pill public-pill-sky text-[11px] tabular-nums" x-text="formatJiwa(item.total_jiwa)"></span>
                            </div>
                        </div>
                        <dl class="mt-3 space-y-2">
                            <div class="flex justify-between items-baseline">
                                <dt class="text-xs font-semibold text-slate-600">Uang</dt>
                                <dd class="text-sm font-bold text-brand-800 tabular-nums" x-text="formatUang(item.total_uang)"></dd>
                            </div>
                            <div class="flex justify-between items-baseline">
                                <dt class="text-xs font-semibold text-slate-600">Beras</dt>
                                <dd class="text-sm font-bold text-amber-800 tabular-nums" x-text="formatBeras(item.total_beras_kg)"></dd>
                            </div>
                        </dl>
                    </article>
                </template>
                <article class="public-report-mobile-total">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="text-sm font-semibold uppercase tracking-[0.12em] text-slate-500">Total</h3>
                        <span class="min-h-9 items-center inline-flex public-pill public-pill-sky text-[11px] tabular-nums" x-text="formatJiwa(totals.total_jiwa)"></span>
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

        <template x-if="isLoading">
            <div class="space-y-2 mt-4 hidden md:block">
                <div class="h-12 bg-slate-100 rounded-xl animate-pulse"></div>
                <div class="h-12 bg-slate-100 rounded-xl animate-pulse"></div>
                <div class="h-12 bg-slate-100 rounded-xl animate-pulse"></div>
            </div>
        </template>

        <div x-show="!isLoading && items.length > 0" class="mt-4 hidden overflow-hidden rounded-[1.1rem] border border-[#d7e5df] bg-[#f8fbf9]/88 md:block" role="region" aria-label="Ringkasan penerimaan zakat">
            <div role="table" aria-label="Data penerimaan per kategori">
                <div role="rowgroup">
                    <div role="row" class="grid grid-cols-[minmax(150px,1.25fr)_0.7fr_1fr_1fr] border-b border-[#d7e5df] bg-brand-50 px-5 py-3 text-[11px] font-bold uppercase tracking-[0.09em] text-brand-800">
                        <span role="columnheader">Kategori</span>
                        <span role="columnheader" class="text-right">Jiwa</span>
                        <span role="columnheader" class="text-right">Rp</span>
                        <span role="columnheader" class="text-right">Kg</span>
                    </div>
                </div>

            <div class="divide-y divide-[#d7e5df]" role="rowgroup">
                <template x-for="item in items" :key="'summary-' + item.category">
                    <article role="row" class="public-report-table-row grid-cols-[minmax(150px,1.25fr)_0.7fr_1fr_1fr]">
                        <p role="cell" class="truncate text-sm font-bold text-slate-950 leading-relaxed" x-text="formatCat(item.category)"></p>
                        <p role="cell" class="text-right text-sm font-semibold text-slate-700 tabular-nums leading-relaxed" x-text="formatJiwaPlain(item.total_jiwa)"></p>
                        <p role="cell" class="text-right text-sm font-semibold text-brand-800 tabular-nums leading-relaxed" x-text="formatUang(item.total_uang)"></p>
                        <p role="cell" class="text-right text-sm font-semibold text-amber-800 tabular-nums leading-relaxed" x-text="formatBeras(item.total_beras_kg)"></p>
                    </article>
                </template>
                <article role="row" class="grid grid-cols-[minmax(150px,1.25fr)_0.7fr_1fr_1fr] items-center gap-2.5 bg-brand-50 px-5 py-3.5">
                    <p role="cell" class="text-sm font-bold uppercase tracking-[0.08em] text-brand-900 leading-relaxed">Total</p>
                    <p role="cell" class="text-right text-sm font-bold text-brand-900 tabular-nums leading-relaxed" x-text="formatJiwaPlain(totals.total_jiwa)"></p>
                    <p role="cell" class="text-right text-sm font-bold text-brand-800 tabular-nums leading-relaxed" x-text="formatUang(totals.total_uang)"></p>
                    <p role="cell" class="text-right text-sm font-bold text-amber-800 tabular-nums leading-relaxed" x-text="formatBeras(totals.total_beras_kg)"></p>
                </article>
            </div>
        </div>
        </div>

        <div x-show="!isLoading && items.length === 0" class="text-center py-10 px-4">
            <svg class="w-12 h-12 mx-auto text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
            <h3 class="font-semibold text-slate-900">Belum ada penerimaan</h3>
            <p class="text-sm text-slate-500 mt-1">Data akan muncul setelah transaksi masuk</p>
        </div>

        <div x-show="!isLoading && latestTransactionAt" class="mt-6 border-t border-[#d7e5df] pt-4 flex justify-between items-center" aria-live="polite" aria-atomic="true">
            <div class="flex items-center gap-2 text-slate-500">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <p class="text-[11px] font-medium" x-text="latestTransactionAgeLabel">@if (!empty($summaryData['latest_transaction_at_wib']))Transaksi terakhir: {{ \Carbon\Carbon::parse($summaryData['latest_transaction_at_wib'], config('zakat.timezone'))->locale('id')->diffForHumans() }}@endif</p>
            </div>
        </div>
    </section>

    <p x-show="error" x-text="error" class="mt-4 rounded-xl border border-red-100 bg-red-50/50 p-3 text-center text-[10px] font-bold text-red-500" role="alert"></p>
</div>
