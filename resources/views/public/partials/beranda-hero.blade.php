<section class="relative group min-h-[300px] overflow-hidden rounded-card border border-white/80 bg-slate-950 shadow-premium ring-1 ring-brand-900/10 sm:min-h-[350px]">
    <div class="absolute inset-0 z-10 bg-gradient-to-r from-slate-950/86 via-slate-950/42 to-transparent"></div>
    <div class="absolute inset-0 z-10 bg-gradient-to-t from-slate-950/72 via-transparent to-transparent"></div>
    <template x-for="(img, i) in carouselImages" :key="i">
        <div x-show="carouselIndex === i"
            x-transition:enter="transition opacity duration-1000"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition opacity duration-1000"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="absolute inset-0">
            <img :src="img" alt="Dokumentasi Masjid An-Nur" class="h-full w-full object-cover object-center transition duration-[3s] group-hover:scale-[1.03]">
        </div>
    </template>

    <div class="relative z-20 flex min-h-[300px] items-end p-5 sm:min-h-[350px] sm:p-8 lg:p-10">
        <div class="max-w-3xl">
            <h2 class="text-[1.7rem] font-extrabold leading-[1.08] tracking-[-0.02em] text-white drop-shadow-2xl sm:text-[2.25rem] lg:text-[2.55rem]">
                Zakat yang tercatat, <span class="text-brand-300">amanah yang terlihat.</span>
            </h2>
            <p class="mt-3 max-w-2xl text-[13px] font-medium leading-relaxed text-brand-50/90 sm:text-[15px]">
                Informasi penerimaan zakat Masjid An-Nur disajikan terbuka untuk jamaah Komplek BPK V Gandul.
            </p>
            <button type="button" @click="activeTab = 'laporan'"
                class="mt-4 inline-flex items-center rounded-full bg-white px-5 py-2.5 text-sm font-bold text-slate-950 shadow-lg shadow-black/15 transition hover:bg-brand-50">
                Lihat Ringkasan Penerimaan
            </button>
        </div>
    </div>
</section>
