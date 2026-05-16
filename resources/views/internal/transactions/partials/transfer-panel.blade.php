<div class="bg-white rounded-2xl shadow-md border border-gray-100 overflow-hidden">
    <div class="bg-emerald-50 px-5 py-3 border-b border-emerald-100 flex items-center gap-2">
        <svg class="h-5 w-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
        </svg>
        <h3 class="font-bold text-emerald-900">Metode Transfer</h3>
    </div>
    <div class="p-5">
        <label class="flex items-center gap-3 cursor-pointer p-3 rounded-xl border border-gray-200 hover:bg-gray-50 transition-colors" :class="is_transfer_global ? 'bg-emerald-50 border-emerald-200' : ''">
            <div class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" x-model="is_transfer_global" @change="handleTfGlobalChange" class="sr-only peer">
                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-emerald-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
            </div>
            <div>
                <p class="text-sm font-bold text-gray-800">Gunakan Metode Transfer?</p>
                <p class="text-[10px] text-gray-500 font-medium">Centang jika terdapat pembayaran via TF</p>
            </div>
        </label>

        <div x-show="is_transfer_global" class="mt-3">
            <button type="button" @click="openTfModal()" class="w-full py-2.5 px-4 bg-white border-2 border-emerald-500 text-emerald-600 rounded-xl text-xs font-black shadow-sm hover:bg-emerald-50 hover:shadow-md transition-all flex items-center justify-center gap-2">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                ATUR ITEM TRANSFER
            </button>
        </div>
    </div>
</div>
