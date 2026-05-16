<div
    id="transaction-form-notice"
    x-show="formNotice"
    x-cloak
    x-transition.opacity
    class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-900 shadow-sm"
    role="alert"
    aria-live="assertive"
>
    <div class="flex items-start gap-3">
        <div class="mt-0.5 rounded-full bg-amber-100 p-2 text-amber-700">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        <div class="flex-1">
            <div class="text-sm font-bold text-amber-800">Perlu Dicek Dulu</div>
            <p class="mt-1 text-sm leading-relaxed" x-text="formNotice"></p>
        </div>
        <button type="button" @click="clearFormNotice()" class="text-amber-500 hover:text-amber-700 transition-colors" aria-label="Tutup pemberitahuan">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>
</div>
