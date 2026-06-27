<div x-show="show_tf_modal"
    class="fixed inset-0 z-[100] overflow-y-auto"
    x-cloak
    x-transition:enter="transition ease-out duration-300"
    x-transition:leave="transition ease-in duration-200">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-slate-900/60 transition-opacity" @click="show_tf_modal = false" x-transition:enter="transition ease-out duration-300" x-transition:leave="transition ease-in duration-200"></div>

        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

        <div class="inline-block transform overflow-hidden rounded-card border border-slate-100 bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-2xl sm:align-middle" x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="transition ease-in duration-200 transform" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
            <div class="flex items-center justify-between bg-brand-600 px-5 py-4">
                <div class="flex items-center gap-3">
                    <div class="rounded-xl bg-white/20 p-2">
                        <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold leading-none text-white">Transfer Bank</h3>
                        <p class="mt-1 text-[10px] font-bold uppercase tracking-[0.14em] text-brand-100">Pilih zakat yang dibayar via transfer</p>
                    </div>
                </div>
                <button @click="show_tf_modal = false" class="text-white/80 hover:text-white transition-colors">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <div class="p-4 sm:p-5">
                <div class="custom-scrollbar max-h-[60vh] space-y-3 overflow-y-auto pr-2">
                    <template x-for="(person, pIdx) in persons" :key="person.id">
                        <div class="space-y-2">
                            <div class="flex items-center gap-2 px-1">
                                <div class="flex h-4 w-4 items-center justify-center rounded-full bg-brand-500 text-[8px] font-bold text-white" x-text="pIdx + 1"></div>
                                <span class="text-xs font-bold uppercase tracking-tight text-slate-700" x-text="person.name || '(Tanpa Nama)'"></span>
                            </div>

                            <div class="space-y-2">
                                <template x-for="(label, cat) in {'fitrah': 'Zakat Fitrah', 'fidyah': 'Fidyah', 'mal': 'Zakat Mal', 'infaq': 'Infaq' }">
                                    <template x-if="person.zakat[cat].active && person.zakat[cat].metode === 'uang'">
                                        <label class="flex cursor-pointer items-center gap-3 rounded-lg border-2 p-3 transition-all"
                                            :class="person.zakat[cat].is_bank_transfer ? 'border-brand-500 bg-brand-50' : 'border-slate-200 bg-white hover:border-brand-200'">
                                            <input type="checkbox" x-model="person.zakat[cat].is_bank_transfer" class="h-4 w-4 rounded border-slate-300 text-brand-600">
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-semibold text-slate-800" x-text="label"></p>
                                                <p class="text-xs text-slate-500 mt-0.5" x-text="'Rp ' + getEffectiveNominal(person, cat)"></p>
                                            </div>
                                        </label>
                                    </template>
                                </template>
                            </div>
                        </div>
                    </template>

                    <div x-show="txs.filter(t => t.metode === 'uang').length === 0" class="rounded-card border-2 border-dashed border-slate-200 bg-slate-50 px-6 py-10 text-center">
                        <svg class="mx-auto mb-3 h-10 w-10 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 00-2 2H6a2 2 0 00-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" /></svg>
                        <p class="text-sm font-bold uppercase tracking-tight text-slate-500">Belum ada item uang</p>
                        <p class="mt-1 text-[10px] font-bold uppercase text-slate-400">Isi nominal zakat terlebih dahulu</p>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between gap-3 bg-slate-50 px-5 py-4 sm:flex-row-reverse">
                <button @click="show_tf_modal = false" type="button" class="w-full rounded-button bg-brand-600 px-6 py-3 text-sm font-bold text-white transition-all hover:bg-brand-700 active:scale-[0.98] sm:w-auto">
                    Simpan
                </button>
                <button @click="is_bank_transfer_global = true; persons.forEach(p => Object.values(p.zakat).forEach(z => { if(z.active && z.metode === 'uang') z.is_bank_transfer = true }));" type="button" class="w-full rounded-button border border-slate-200 bg-white px-4 py-3 text-xs font-bold text-slate-600 transition-all hover:bg-slate-100 sm:w-auto">
                    Pilih Semua
                </button>
            </div>
        </div>
    </div>
</div>
