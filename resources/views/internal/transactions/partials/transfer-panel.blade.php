<div class="ui-card overflow-hidden">
    <div class="ui-card-header ui-card-header-emerald px-5 py-3">
        <svg class="ui-card-header-icon text-brand-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
        </svg>
        <h3 class="ui-card-header-title text-brand-900">Metode Transfer</h3>
    </div>
    <div class="p-4">
        <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 p-3 transition-colors hover:bg-slate-50" :class="is_bank_transfer_global ? 'bg-brand-50 border-brand-200' : ''">
            <div class="relative inline-flex cursor-pointer items-center">
                <input type="checkbox" x-model="is_bank_transfer_global" @change="handleTfGlobalChange" class="sr-only peer">
                <div class="peer h-6 w-11 rounded-full bg-slate-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-slate-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-brand-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-brand-300"></div>
            </div>
            <div>
                <p class="text-sm font-bold text-slate-800">Gunakan Transfer?</p>
                <p class="text-[10px] font-medium text-slate-500">Untuk pembayaran via TF</p>
            </div>
        </label>

        <div x-show="is_bank_transfer_global" class="mt-3">
            <button type="button" @click="openTfModal()" class="ui-btn ui-btn-secondary w-full border-2 border-brand-500 px-4 py-2.5 text-xs font-bold text-brand-600 hover:bg-brand-50">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                Atur Item Transfer
            </button>
        </div>
    </div>
</div>
