<div class="ui-card-strong sticky bottom-4 z-10 mt-4 bg-white/95 p-3 backdrop-blur-md sm:p-4">
    <div class="mb-3 flex flex-col gap-3 border-b border-slate-100 pb-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-sm font-bold text-slate-900">{{ isset($isEdit) ? 'Selesaikan perubahan transaksi' : 'Simpan transaksi yang sudah diperiksa' }}</p>
            <p class="text-xs text-slate-500">{{ isset($isEdit) ? 'Cek nominal, kategori, dan pembayar.' : 'Simpan setelah data anggota terisi.' }}</p>
        </div>
        <div class="inline-flex w-full justify-center rounded-full border border-brand-100 bg-brand-50 px-3 py-1 text-[11px] font-semibold text-brand-700 sm:w-auto">
            <span x-text="submitting ? 'Sedang menyimpan' : 'Siap diproses'"></span>
        </div>
    </div>

    @if(isset($isEdit))
        <div class="flex flex-col items-stretch gap-3 sm:flex-row">
            <a href="{{ route('internal.transactions.index') }}"
                class="ui-btn w-full bg-slate-100 px-5 py-3 text-sm text-slate-600 hover:bg-slate-200 focus:ring-slate-300 sm:flex-1">
                Kembali
            </a>
            <x-primary-button
                x-bind:disabled="submitting || !hasChanged"
                x-bind:class="{'ui-btn-loading': submitting, 'opacity-50 cursor-not-allowed': !submitting && !hasChanged}"
                class="w-full py-3 text-sm sm:flex-[2]">
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
            </x-primary-button>
        </div>
    @else
        <x-primary-button
            x-bind:disabled="submitting"
            x-bind:class="{'ui-btn-loading': submitting}"
            class="w-full py-3 text-sm">
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
        </x-primary-button>
    @endif

    <p class="mt-3 text-center text-[11px] font-semibold text-slate-400">
        <span x-show="!submitting">{{ isset($isEdit) ? 'Perubahan akan menggantikan data lama pada transaksi ini.' : 'Satu kali simpan akan merekam seluruh data anggota yang sedang aktif.' }}</span>
        <span x-show="submitting" class="text-brand-600">Mohon tunggu sebentar, jangan tutup halaman ini...</span>
    </p>
</div>
