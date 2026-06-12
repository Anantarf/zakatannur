<div x-show="notification.show"
    x-cloak
    x-transition:enter="transition ease-out duration-300 transform"
    x-transition:enter-start="-translate-y-10 opacity-0 scale-95"
    x-transition:enter-end="translate-y-0 opacity-100 scale-100"
    x-transition:leave="transition ease-in duration-300 transform"
    x-transition:leave-start="translate-y-0 opacity-100 scale-100"
    x-transition:leave-end="-translate-y-10 opacity-0 scale-95"
    class="fixed top-20 right-4 sm:right-6 lg:right-8 z-[60] w-[90%] sm:w-auto max-w-sm origin-top-right">
    <div class="bg-brand-600/95 text-white px-5 py-4 rounded-xl shadow-[0_15px_40px_rgba(16,185,129,0.3)] border border-brand-500/50 flex flex-col gap-1 backdrop-blur-sm">
        <div class="flex items-start gap-4">
            <div class="w-8 h-8 rounded-full bg-white flex items-center justify-center shrink-0 shadow-sm ring-1 ring-brand-200 mt-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-brand-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="flex-1">
                <p class="text-[12px] font-extrabold tracking-widest text-brand-100 uppercase opacity-90">Penerimaan Baru</p>
                <p class="text-[15px] sm:text-base font-bold leading-normal mt-0.5" x-text="notification.message"></p>
            </div>
        </div>
    </div>
</div>
