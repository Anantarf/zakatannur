<div x-show="show_tf_modal"
    class="fixed inset-0 z-[100] overflow-y-auto"
    x-cloak>
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity bg-gray-900/60 backdrop-blur-sm" @click="show_tf_modal = false"></div>

        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

        <div class="inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-gray-100">
            <div class="bg-emerald-600 px-6 py-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-white/20 rounded-xl">
                        <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-black text-white leading-none">Pilih Item Transfer</h3>
                        <p class="text-[10px] text-emerald-100 font-bold uppercase tracking-widest mt-1">Hanya item berupa uang yang dapat ditandai TF</p>
                    </div>
                </div>
                <button @click="show_tf_modal = false" class="text-white/80 hover:text-white transition-colors">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <div class="p-6">
                <div class="space-y-3 max-h-[60vh] overflow-y-auto pr-2 custom-scrollbar">
                    <template x-for="(person, pIdx) in persons" :key="person.id">
                        <div class="space-y-2">
                            <div class="flex items-center gap-2 px-1">
                                <div class="h-4 w-4 bg-emerald-500 rounded-full flex items-center justify-center text-[8px] font-bold text-white" x-text="pIdx + 1"></div>
                                <span class="text-xs font-black text-gray-700 uppercase tracking-tight" x-text="person.name || '(Tanpa Nama)'"></span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pb-3">
                                <template x-for="(label, cat) in {'fitrah': 'Zakat Fitrah', 'fidyah': 'Fidyah', 'mal': 'Zakat Mal', 'infaq': 'Infaq' }">
                                    <div x-show="person.zakat[cat].active && person.zakat[cat].metode === 'uang'"
                                        class="relative group">
                                        <label class="flex items-center gap-3 p-3 rounded-2xl border-2 transition-all cursor-pointer"
                                            :class="person.zakat[cat].is_transfer ? 'border-emerald-500 bg-emerald-50 shadow-sm' : 'border-gray-100 bg-gray-50 hover:border-gray-200'">
                                            <input type="checkbox" x-model="person.zakat[cat].is_transfer" class="h-5 w-5 text-emerald-600 border-gray-300 rounded-lg focus:ring-emerald-500 transition-all">
                                            <div class="flex-1">
                                                <p class="text-[11px] font-black text-gray-800 uppercase tracking-tight" x-text="label"></p>
                                                <p class="text-[10px] font-bold text-gray-500" x-text="'Rp ' + getEffectiveNominal(person, cat)"></p>
                                            </div>
                                            <div x-show="person.zakat[cat].is_transfer" class="text-emerald-600">
                                                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                                            </div>
                                        </label>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>

                    <div x-show="txs.filter(t => t.metode === 'uang').length === 0" class="py-12 px-6 text-center bg-gray-50 rounded-3xl border-2 border-dashed border-gray-200">
                        <svg class="h-10 w-10 text-gray-300 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 00-2 2H6a2 2 0 00-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" /></svg>
                        <p class="text-sm font-bold text-gray-500 uppercase tracking-tight">Belum ada item uang yang dipilih</p>
                        <p class="text-[10px] text-gray-400 font-bold mt-1 uppercase">SILAKAN ISI NOMINAL ZAKAT TERLEBIH DAHULU PADA FORM DI LUAR MODAL INI</p>
                    </div>
                </div>
            </div>

            <div class="bg-gray-50 px-6 py-4 flex items-center justify-between sm:flex-row-reverse gap-3">
                <button @click="show_tf_modal = false" type="button" class="w-full sm:w-auto px-6 py-3 bg-emerald-600 text-white rounded-2xl font-black text-sm shadow-lg shadow-emerald-100 hover:bg-emerald-700 transition-all active:scale-[0.98]">
                    Simpan
                </button>
                <button @click="is_transfer_global = true; persons.forEach(p => Object.values(p.zakat).forEach(z => { if(z.active && z.metode === 'uang') z.is_transfer = true }));" type="button" class="w-full sm:w-auto px-4 py-3 bg-white border border-gray-200 text-gray-600 rounded-2xl font-bold text-xs hover:bg-gray-100 transition-all">
                    Pilih Semua (TF)
                </button>
            </div>
        </div>
    </div>
</div>
