<section class="grid items-stretch gap-3 rounded-card border border-white/80 bg-white/[0.92] p-3 shadow-lg shadow-brand-900/5 ring-1 ring-brand-100/50 backdrop-blur lg:grid-cols-[5fr_3fr]">
    <div class="flex min-h-[170px] flex-col justify-center rounded-[1.25rem] bg-[linear-gradient(135deg,rgba(255,255,255,0.98),rgba(240,253,250,0.86))] px-5 py-5 ring-1 ring-brand-100/70 sm:px-6">
        <p class="mb-2 text-[10px] font-bold uppercase tracking-[0.16em] text-brand-700">Portal Zakat Masjid An-Nur</p>
        <h1 class="max-w-2xl text-[1.55rem] font-extrabold leading-[1.1] tracking-[-0.015em] text-slate-950 sm:text-[1.95rem] lg:text-[2.15rem]">
            Informasi zakat yang ringkas, terbuka, dan mudah dipantau.
        </h1>
        <p class="mt-2 max-w-2xl text-[13px] font-medium leading-relaxed text-slate-600 sm:text-[14.5px]">
            Jamaah dapat melihat penerimaan zakat Masjid An-Nur tanpa menunggu rekap manual.
        </p>
        @if ((($summaryData['totals']['total_uang'] ?? 0) > 0) || (($summaryData['totals']['total_jiwa'] ?? 0) > 0))
            <div class="mt-5 grid gap-3 sm:grid-cols-2">
                @if (($summaryData['totals']['total_uang'] ?? 0) > 0)
                    <div class="public-subcard public-subcard-brand px-5 py-3">
                        <p class="mb-1 text-[10px] font-bold uppercase tracking-[0.12em] text-brand-700">Total Penerimaan Uang</p>
                        <p class="text-2xl font-black tabular-nums text-brand-950 sm:text-3xl">Rp{{ number_format($summaryData['totals']['total_uang'], 0, ',', '.') }}</p>
                        <p class="mt-0.5 text-[11px] font-semibold text-brand-700">Tahun {{ $selectedYear }}</p>
                    </div>
                @endif
                @if (($summaryData['totals']['total_jiwa'] ?? 0) > 0)
                    <div class="public-subcard px-5 py-3">
                        <p class="mb-1 text-[10px] font-bold uppercase tracking-[0.12em] text-slate-500">Total Jiwa Zakat Fitrah</p>
                        <p class="text-2xl font-black tabular-nums text-slate-950 sm:text-3xl">{{ number_format($summaryData['totals']['total_jiwa'], 0, ',', '.') }}</p>
                        <p class="mt-0.5 text-[11px] font-semibold text-slate-500">Jiwa terdaftar</p>
                    </div>
                @endif
            </div>
        @endif
        <div class="mt-4 flex flex-wrap items-center gap-2">
            <button type="button" @click="activeTab = 'laporan'"
                class="inline-flex w-fit items-center rounded-full bg-brand-700 px-4 py-2.5 text-[12px] font-bold text-white shadow-md shadow-brand-700/25 transition hover:bg-brand-800 sm:text-sm">
                Buka Ringkasan
            </button>
            <button type="button" @click="activeTab = 'grafik'"
                class="inline-flex w-fit items-center rounded-full border border-slate-200 bg-white px-4 py-2.5 text-[12px] font-bold text-slate-700 shadow-sm transition hover:bg-slate-50 sm:text-sm">
                Lihat Grafik
            </button>
        </div>
    </div>

    <div class="relative group min-h-[170px] overflow-hidden rounded-[1.25rem] bg-slate-100 ring-1 ring-slate-200/70">
        <template x-for="(img, i) in carouselImages" :key="i">
            <div x-show="carouselIndex === i"
                x-transition:enter="transition opacity duration-1000"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition opacity duration-1000"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="absolute inset-0">
                <img :src="img" alt="Dokumentasi Masjid An-Nur"
                    :loading="i === 0 ? 'eager' : 'lazy'"
                    :fetchpriority="i === 0 ? 'high' : 'auto'"
                    decoding="async"
                    class="h-full w-full object-cover object-center transition duration-[3s] group-hover:scale-[1.02]">
            </div>
        </template>
        <div class="absolute inset-0 bg-gradient-to-t from-slate-950/30 via-transparent to-transparent"></div>
        <div class="absolute bottom-3 left-3 rounded-full bg-white/92 px-3 py-1.5 text-[11px] font-bold text-slate-800 shadow-sm backdrop-blur">
            Komplek BPK V Gandul
        </div>
    </div>
</section>
