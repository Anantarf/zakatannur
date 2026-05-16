<div class="sticky bottom-4 z-10 pt-4 mt-6 bg-white/95 backdrop-blur-md p-4 rounded-2xl shadow-xl border border-emerald-100">
    @if(isset($isEdit))
        <div class="flex items-stretch gap-3">
            <a href="{{ route('internal.transactions.index') }}"
                class="flex-1 flex justify-center items-center gap-2 rounded-xl bg-slate-100 px-6 py-4 text-sm font-bold text-slate-600 hover:bg-slate-200 transition-all active:scale-[0.98]">
                Kembali
            </a>
            <x-emerald-button
                x-bind:disabled="submitting || !hasChanged"
                x-bind:class="{'opacity-50 cursor-not-allowed': submitting || !hasChanged}"
                class="flex-[2] py-4 text-base">
                <template x-if="submitting">
                    <div class="flex items-center gap-2">
                        <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>Memproses...</span>
                    </div>
                </template>

                <template x-if="!submitting">
                    <div class="flex items-center gap-2">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                        <span x-text="hasChanged ? 'Simpan Perubahan' : 'Tidak Ada Perubahan'"></span>
                    </div>
                </template>
            </x-emerald-button>
        </div>
    @else
        <x-emerald-button
            x-bind:disabled="submitting"
            x-bind:class="{'opacity-50 cursor-not-allowed': submitting}"
            class="w-full py-4 text-base">
            <template x-if="submitting">
                <div class="flex items-center gap-2">
                    <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Sedang Memproses...</span>
                </div>
            </template>

            <template x-if="!submitting">
                <div class="flex items-center gap-2">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                    </svg>
                    <span>Proses & Simpan Transaksi</span>
                </div>
            </template>
        </x-emerald-button>
    @endif

    <p class="text-center text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-3">
        <span x-show="!submitting">{{ isset($isEdit) ? 'Data lama akan digantikan dengan input baru di halaman ini' : 'Satu Kali Klik untuk Menyimpan Seluruh Data Anggota' }}</span>
        <span x-show="submitting" class="text-emerald-600">Mohon tunggu sebentar, jangan tutup halaman ini...</span>
    </p>
</div>
