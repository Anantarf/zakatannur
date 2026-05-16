<div class="relative group h-[280px] sm:h-[450px] rounded-[2.5rem] overflow-hidden shadow-2xl shadow-emerald-900/10">
    <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent z-10"></div>
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

    <div class="absolute bottom-0 left-0 right-0 p-8 sm:p-14 z-20">
        <div class="max-w-3xl">
            <h2 class="text-4xl sm:text-6xl font-black text-white leading-[1.1] drop-shadow-2xl tracking-tighter">
                Amanah Dalam<br><span class="text-emerald-400">Mengelola Kebaikan.</span>
            </h2>
        </div>
    </div>
</div>
