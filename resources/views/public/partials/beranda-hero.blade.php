<section class="relative group h-[240px] sm:h-[320px] overflow-hidden rounded-[1.6rem] sm:rounded-[1.8rem] shadow-2xl shadow-emerald-950/10 ring-1 ring-black/5">
    <div class="absolute inset-0 z-10 bg-gradient-to-t from-black/70 via-black/20 to-transparent"></div>
    <template x-for="(img, i) in carouselImages" :key="i">
        <div x-show="carouselIndex === i"
            x-transition:enter="transition opacity duration-1000"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition opacity duration-1000"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="absolute inset-0">
            <img :src="img" alt="Dokumentasi Masjid An-Nur" class="w-full h-full object-cover transform hover:scale-105 transition duration-[3s]">
        </div>
    </template>

    <div class="absolute inset-x-0 bottom-0 z-20 p-5 sm:p-7">
        <div class="max-w-3xl">
            <p class="mb-2 text-[10px] sm:text-[11px] font-semibold uppercase tracking-[0.3em] text-emerald-100/90">
                Transparansi Penerimaan Zakat
            </p>
            <h2 class="text-[2rem] sm:text-[3rem] font-black text-white leading-[0.98] drop-shadow-2xl tracking-[-0.035em]">
                Amanah Dalam<br><span class="text-emerald-400">Mengelola Kebaikan.</span>
            </h2>
            <p class="mt-3 max-w-xl text-[13px] sm:text-[15px] font-medium leading-relaxed text-emerald-50/90">
                Sistem manajemen zakat terintegrasi untuk memberikan kemudahan, keamanan, dan transparansi penuh kepada jamaah.
            </p>
        </div>
    </div>
</section>
