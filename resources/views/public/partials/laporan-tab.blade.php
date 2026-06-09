<div x-show="activeTab === 'laporan'"
    x-transition:enter="transition ease-out duration-500"
    x-transition:enter-start="opacity-0 translate-y-4"
    x-transition:enter-end="opacity-100 translate-y-0">
    <div class="mx-1 space-y-3 md:hidden">
        <section class="public-report-intro">
            <p class="public-report-eyebrow">Ringkasan penerimaan</p>
            <h2 class="mt-2 text-[1.4rem] font-semibold leading-tight text-neutral-950">Lihat total utama per kategori zakat</h2>
            <p class="public-report-copy">
                Data ditata agar jamaah bisa membaca jumlah jiwa, uang, dan beras tanpa perlu melihat tabel yang padat.
            </p>
        </section>

        <template x-for="item in items" :key="'card-' + item.category">
            <article class="public-report-card">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-[12px] font-medium tracking-[0.03em] text-neutral-500">Kategori</p>
                        <h3 class="mt-1 text-[1.28rem] font-semibold leading-tight text-neutral-950" x-text="formatCat(item.category)"></h3>
                    </div>
                    <span class="public-pill public-pill-sky tabular-nums" x-text="formatJiwa(item.total_jiwa)"></span>
                </div>

                <dl class="mt-4 grid grid-cols-2 gap-3">
                    <div class="public-subcard public-subcard-brand p-4">
                        <dd class="text-[18px] font-semibold text-brand-800 tabular-nums text-right" x-text="formatUang(item.total_uang)"></dd>
                    </div>
                    <div class="public-subcard public-subcard-amber p-4">
                        <dd class="text-[18px] font-semibold text-amber-800 tabular-nums text-right" x-text="formatBeras(item.total_beras_kg)"></dd>
                    </div>
                </dl>
            </article>
        </template>

        <div x-show="items.length > 0" class="public-section public-section-muted">
            <p class="text-[12px] font-medium tracking-[0.03em] text-neutral-500">Total penerimaan</p>
            <div class="mt-3 grid grid-cols-1 gap-3 text-[19px] tabular-nums">
                <span class="font-semibold text-neutral-950" x-text="formatJiwa(totals.total_jiwa)"></span>
                <span class="font-semibold text-brand-800" x-text="formatUang(totals.total_uang)"></span>
                <span class="font-semibold text-amber-800" x-text="formatBeras(totals.total_beras_kg)"></span>
            </div>
        </div>

        <p x-show="items.length === 0" class="public-shell px-6 py-10 text-center text-sm font-bold text-neutral-400">Belum ada data penerimaan masuk tahun ini.</p>
    </div>

    <div class="mx-1 hidden sm:mx-0 md:block">
        <div class="public-report-shell lg:p-5">
            <div class="flex items-end justify-between gap-4 border-b border-neutral-200/75 px-2 pb-2.5">
                <div>
                    <p class="public-report-eyebrow">Ringkasan penerimaan</p>
                    <h2 class="mt-1 text-[1.1rem] font-semibold leading-tight text-neutral-950">Kategori utama dalam satu panel ringkas</h2>
                </div>
                <div class="hidden lg:flex items-center gap-2 text-[11px] text-neutral-500">
                    <span class="public-pill public-pill-sky px-2.5 py-1">Jiwa</span>
                    <span class="public-pill public-pill-brand px-2.5 py-1">Uang</span>
                    <span class="public-pill public-pill-amber px-2.5 py-1">Beras</span>
                </div>
            </div>

            <div class="mt-2.5 grid grid-cols-[1.28fr_0.72fr_0.98fr_0.98fr] items-center gap-2.5 px-2 pb-1.5 text-[10px] font-medium tracking-[0.03em] text-neutral-500">
                <span>Kategori</span>
                <span class="text-right">Jiwa</span>
                <span class="text-right">Uang</span>
                <span class="text-right">Beras</span>
            </div>

            <div x-show="items.length > 0" class="space-y-2">
                <template x-for="item in items" :key="'summary-' + item.category">
                    <article class="public-report-row grid-cols-[1.28fr_0.72fr_0.98fr_0.98fr]">
                        <div class="min-w-0">
                            <p class="truncate text-[1.02rem] font-semibold text-neutral-950" x-text="formatCat(item.category)"></p>
                        </div>
                        <div class="public-subcard public-subcard-sky px-3 py-2 text-right">
                            <p class="text-[0.98rem] font-semibold text-sky-950 tabular-nums" x-text="formatJiwaPlain(item.total_jiwa)"></p>
                        </div>
                        <div class="public-subcard public-subcard-brand px-3 py-2 text-right">
                            <p class="text-[0.96rem] font-semibold text-brand-800 tabular-nums" x-text="formatUang(item.total_uang)"></p>
                        </div>
                        <div class="public-subcard public-subcard-amber px-3 py-2 text-right">
                            <p class="text-[0.96rem] font-semibold text-amber-800 tabular-nums" x-text="formatBeras(item.total_beras_kg)"></p>
                        </div>
                    </article>
                </template>
            </div>

            <p x-show="items.length === 0" class="px-8 py-12 text-center text-base font-medium text-neutral-400">Belum ada data penerimaan masuk tahun ini.</p>
        </div>
    </div>

    <p x-show="error" x-text="error" class="mt-4 rounded-xl border border-red-100 bg-red-50/50 p-3 text-[10px] font-bold text-red-500 text-center" role="alert"></p>
</div>
