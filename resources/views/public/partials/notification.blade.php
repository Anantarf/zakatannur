<div x-show="notification.show"
    x-cloak
    x-transition:enter="transition ease-out duration-300 transform"
    x-transition:enter-start="-translate-y-10 opacity-0 scale-95"
    x-transition:enter-end="translate-y-0 opacity-100 scale-100"
    x-transition:leave="transition ease-in duration-300 transform"
    x-transition:leave-start="translate-y-0 opacity-100 scale-100"
    x-transition:leave-end="-translate-y-10 opacity-0 scale-95"
    class="fixed top-6 left-1/2 -translate-x-1/2 z-[999] w-[90%] sm:w-auto sm:min-w-[360px] max-w-md origin-top">
    <div class="relative bg-brand-600/95 text-white px-5 py-3.5 pr-11 rounded-xl shadow-[0_15px_40px_rgba(20,184,166,0.28)] border border-brand-500/50 backdrop-blur-sm">
        <button type="button" @click="dismissNotification()" class="absolute top-3.5 right-3 text-brand-200 hover:text-white transition-colors bg-white/10 hover:bg-white/20 rounded-full p-1" title="Tutup Notifikasi">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
        <div class="flex items-center gap-4">
            <div class="w-10 h-10 rounded-full bg-white flex items-center justify-center shrink-0 shadow-sm ring-4 ring-brand-700/30">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-brand-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="flex-1 py-0.5">
                <p class="text-[10px] font-bold tracking-widest text-brand-100 uppercase opacity-90 mb-0.5">Penerimaan Baru</p>
                <p class="text-[15px] sm:text-[16px] font-bold leading-tight" x-text="notification.message"></p>
            </div>
        </div>
    </div>
</div>
