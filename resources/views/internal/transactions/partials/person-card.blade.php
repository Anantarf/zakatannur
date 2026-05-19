<div class="ui-card relative mb-4 border-gray-200 p-4 transition-all hover:border-emerald-200 sm:rounded-2xl sm:p-5">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between xl:justify-start gap-3 mb-4">
        <div class="flex items-center gap-2 flex-1 w-full lg:max-w-sm">
            <div class="h-6 w-6 sm:h-7 sm:w-7 bg-emerald-500 text-white rounded-full flex justify-center items-center text-xs font-bold shadow-sm shrink-0" x-text="index + 1"></div>
            <input type="text" x-model="person.name" class="muzakki-name-input ui-input w-full text-sm" placeholder="Nama..." required>
        </div>
        <button type="button" @click="removePerson(index)" x-show="persons.length > 1" class="ui-btn ui-btn-secondary w-full border-red-100 bg-red-50 py-2 text-xs text-red-500 hover:bg-red-50 hover:text-red-600 sm:w-auto sm:px-3 sm:py-1.5">
            Hapus Baris
        </button>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 border-t border-gray-100 pt-4">
        <div class="rounded-xl border p-3 transition-colors" :class="person.zakat.fitrah.active ? 'border-emerald-400 bg-emerald-50/30 ring-1 ring-emerald-400' : 'border-gray-200 bg-gray-50/50'">
            <label class="flex items-center gap-2 cursor-pointer font-bold text-gray-800 text-sm">
                <input type="checkbox" x-model="person.zakat.fitrah.active" class="h-4 w-4 text-emerald-600 border-gray-300 rounded focus:ring-emerald-500">
                Zakat Fitrah
            </label>
            <div x-show="person.zakat.fitrah.active" class="mt-3 space-y-3" x-collapse>
                <div class="space-y-1">
                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Bentuk Zakat Fitrah</label>
                    <select x-model="person.zakat.fitrah.metode" class="w-full text-xs rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        <option value="uang">Uang (Rp)</option>
                        <option value="beras">Beras (Kg)</option>
                    </select>
                </div>

                <div class="space-y-1">
                    <label class="flex items-center gap-2 cursor-pointer text-[10px] font-bold text-emerald-700 uppercase tracking-wider">
                        <input type="checkbox" x-model="person.zakat.fitrah.is_custom" class="h-3 w-3 text-emerald-600 border-gray-300 rounded">
                        Nominal Khusus
                    </label>

                    <template x-if="!person.zakat.fitrah.is_custom">
                        <div class="text-[11px] bg-emerald-100/50 text-emerald-800 px-2 py-1.5 rounded border border-emerald-200 font-medium italic">
                            Sesuai Standar
                        </div>
                    </template>

                    <template x-if="person.zakat.fitrah.is_custom">
                        <div class="flex items-center w-full rounded-lg border overflow-hidden shadow-sm focus-within:ring-1 bg-white mt-1.5 transition-all"
                            :class="isBelowStandard(person, 'fitrah') ? 'border-red-500 focus-within:ring-red-500 focus-within:border-red-500' : 'border-emerald-300 focus-within:ring-emerald-500 focus-within:border-emerald-500'">
                            <div x-show="person.zakat.fitrah.metode !== 'beras'" class="px-2.5 py-1.5 text-emerald-700 font-black text-[10px] border-r shrink-0" :class="isBelowStandard(person, 'fitrah') ? 'bg-red-50 border-red-100' : 'bg-emerald-50 border-emerald-100'">Rp</div>
                            <input type="text" inputmode="decimal" x-model="person.zakat.fitrah.nominal"
                                @input="person.zakat.fitrah.metode !== 'beras' ? person.zakat.fitrah.nominal = formatCurrency($event.target.value) : person.zakat.fitrah.nominal = formatBeras($event.target.value)"
                                :placeholder="person.zakat.fitrah.metode === 'beras' ? 'Isi berat (Kg)...' : '0'"
                                class="flex-1 w-full text-xs border-0 focus:ring-0 py-1.5 px-3 bg-transparent">
                            <div x-show="person.zakat.fitrah.metode === 'beras'" class="px-2.5 py-1.5 text-emerald-700 font-black text-[10px] border-l shrink-0" :class="isBelowStandard(person, 'fitrah') ? 'bg-red-50 border-red-100' : 'bg-emerald-50 border-emerald-100'">Kg</div>
                        </div>
                    </template>
                </div>

                <div class="pt-1">
                    <p class="text-[9px] text-emerald-600 font-bold leading-tight">
                        * Standar: Rp {{ number_format($fitrahUang, 0, ',', '.') }} atau {{ $berasPerJiwa }}kg / 3.5L per jiwa
                    </p>
                </div>
            </div>
        </div>

        <div class="rounded-xl border p-3 transition-colors" :class="person.zakat.fidyah.active ? 'border-amber-400 bg-amber-50/30 ring-1 ring-amber-400' : 'border-gray-200 bg-gray-50/50'">
            <label class="flex items-center gap-2 cursor-pointer font-bold text-gray-800 text-sm">
                <input type="checkbox" x-model="person.zakat.fidyah.active" class="h-4 w-4 text-amber-600 border-gray-300 rounded focus:ring-amber-500">
                Fidyah
            </label>
            <div x-show="person.zakat.fidyah.active" class="mt-3 space-y-3" x-collapse>
                <div class="flex items-center bg-white rounded-lg shadow-sm border border-gray-300 overflow-hidden focus-within:border-amber-500 focus-within:ring-1 focus-within:ring-amber-500">
                    <input type="number" min="1" x-model="person.zakat.fidyah.hari" placeholder="Jml. Hari" class="w-full text-xs border-0 focus:ring-0" :required="person.zakat.fidyah.active">
                    <span class="px-2 text-xs font-medium text-gray-500 border-l border-gray-200 bg-gray-50">Hari</span>
                </div>
                <div class="space-y-1">
                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Bentuk Fidyah</label>
                    <select x-model="person.zakat.fidyah.metode" class="w-full text-xs rounded-lg border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500">
                        <option value="uang">Uang (Rp)</option>
                        <option value="beras">Beras (Kg)</option>
                    </select>
                </div>

                <div class="space-y-1">
                    <label class="flex items-center gap-2 cursor-pointer text-[10px] font-bold text-amber-700 uppercase tracking-wider">
                        <input type="checkbox" x-model="person.zakat.fidyah.is_custom" class="h-3 w-3 text-amber-600 border-gray-300 rounded">
                        Nominal Khusus
                    </label>

                    <template x-if="!person.zakat.fidyah.is_custom">
                        <div class="text-[11px] bg-amber-100/50 text-amber-800 px-2 py-1.5 rounded border border-amber-200 font-medium italic">
                            Sesuai Standar
                        </div>
                    </template>

                    <template x-if="person.zakat.fidyah.is_custom">
                        <div class="flex items-center w-full rounded-lg border overflow-hidden shadow-sm focus-within:ring-1 bg-white mt-1.5 transition-all"
                            :class="isBelowStandard(person, 'fidyah') ? 'border-red-500 focus-within:ring-red-500 focus-within:border-red-500' : 'border-amber-300 focus-within:ring-amber-500 focus-within:border-amber-500'">
                            <div x-show="person.zakat.fidyah.metode !== 'beras'" class="px-2.5 py-1.5 text-amber-700 font-black text-[10px] border-r shrink-0" :class="isBelowStandard(person, 'fidyah') ? 'bg-red-50 border-red-100' : 'bg-amber-50 border-amber-100'">Rp</div>
                            <input type="text" inputmode="decimal" x-model="person.zakat.fidyah.nominal"
                                @input="person.zakat.fidyah.metode !== 'beras' ? person.zakat.fidyah.nominal = formatCurrency($event.target.value) : person.zakat.fidyah.nominal = formatBeras($event.target.value)"
                                :placeholder="person.zakat.fidyah.metode === 'beras' ? 'Isi berat (Kg)...' : '0'"
                                class="flex-1 w-full text-xs border-0 focus:ring-0 py-1.5 px-3 bg-transparent">
                            <div x-show="person.zakat.fidyah.metode === 'beras'" class="px-2.5 py-1.5 text-amber-700 font-black text-[10px] border-l shrink-0" :class="isBelowStandard(person, 'fidyah') ? 'bg-red-50 border-red-100' : 'bg-amber-50 border-amber-100'">Kg</div>
                        </div>
                    </template>
                </div>

                <div class="pt-1">
                    <p class="text-[9px] text-amber-600 font-bold leading-tight">
                        * Standar: Rp {{ number_format($fidyahUang, 0, ',', '.') }} atau {{ $berasPerJiwa }}kg / 3.5L per hari
                    </p>
                </div>
            </div>
        </div>

        <div class="rounded-xl border p-3 transition-colors" :class="person.zakat.mal.active ? 'border-blue-400 bg-blue-50/30 ring-1 ring-blue-400' : 'border-gray-200 bg-gray-50/50'">
            <label class="flex items-center gap-2 cursor-pointer font-bold text-gray-800 text-sm">
                <input type="checkbox" x-model="person.zakat.mal.active" class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                Zakat Mal
            </label>
            <div x-show="person.zakat.mal.active" class="mt-3 space-y-2" x-collapse>
                <div class="space-y-1">
                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Bentuk Zakat Mal</label>
                    <select x-model="person.zakat.mal.metode" class="w-full text-xs rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="uang">Uang (Rp)</option>
                        <option value="beras">Beras (Kg)</option>
                    </select>
                </div>
                <div class="flex items-center w-full rounded-lg border overflow-hidden shadow-sm focus-within:ring-1 bg-white mt-1.5 transition-all"
                    :class="isBelowStandard(person, 'mal') ? 'border-red-500 focus-within:ring-red-500 focus-within:border-red-500' : 'border-gray-300 focus-within:ring-blue-500 focus-within:border-blue-500'">
                    <div x-show="person.zakat.mal.metode !== 'beras'" class="px-2.5 py-1.5 text-blue-700 font-black text-[10px] border-r shrink-0" :class="isBelowStandard(person, 'mal') ? 'bg-red-50 border-red-100' : 'bg-blue-50 border-blue-100'">Rp</div>
                    <input type="text" :inputmode="person.zakat.mal.metode === 'beras' ? 'decimal' : 'numeric'" x-model="person.zakat.mal.nominal"
                        @input="person.zakat.mal.metode !== 'beras' ? person.zakat.mal.nominal = formatCurrency($event.target.value) : person.zakat.mal.nominal = formatBeras($event.target.value)"
                        :placeholder="person.zakat.mal.metode === 'beras' ? '0.00' : '0'" class="flex-1 w-full text-xs border-0 focus:ring-0 py-1.5 px-3 bg-transparent" :required="person.zakat.mal.active">
                    <div x-show="person.zakat.mal.metode === 'beras'" class="px-2.5 py-1.5 text-blue-700 font-black text-[10px] border-l shrink-0" :class="isBelowStandard(person, 'mal') ? 'bg-red-50 border-red-100' : 'bg-blue-50 border-blue-100'">Kg</div>
                </div>
            </div>
        </div>

        <div class="rounded-xl border p-3 transition-colors" :class="person.zakat.infaq.active ? 'border-cyan-400 bg-cyan-50/40 ring-1 ring-cyan-400' : 'border-gray-200 bg-gray-50/50'">
            <label class="flex items-center gap-2 cursor-pointer font-bold text-gray-800 text-sm">
                <input type="checkbox" x-model="person.zakat.infaq.active" class="h-4 w-4 text-cyan-600 border-gray-300 rounded focus:ring-cyan-500">
                Infaq Sedekah
            </label>
            <div x-show="person.zakat.infaq.active" class="mt-3 space-y-2" x-collapse>
                <div class="space-y-1">
                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Bentuk Infaq Sedekah</label>
                    <select x-model="person.zakat.infaq.metode" class="w-full text-xs rounded-lg border-gray-300 shadow-sm focus:border-cyan-500 focus:ring-cyan-500">
                        <option value="uang">Uang (Rp)</option>
                        <option value="beras">Beras (Kg)</option>
                    </select>
                </div>
                <div class="flex items-center w-full rounded-lg border overflow-hidden shadow-sm focus-within:ring-1 bg-white mt-1.5 transition-all"
                    :class="isBelowStandard(person, 'infaq') ? 'border-red-500 focus-within:ring-red-500 focus-within:border-red-500' : 'border-cyan-300 focus-within:ring-cyan-500 focus-within:border-cyan-500'">
                    <div x-show="person.zakat.infaq.metode !== 'beras'" class="px-2.5 py-1.5 text-cyan-700 font-black text-[10px] border-r shrink-0" :class="isBelowStandard(person, 'infaq') ? 'bg-red-50 border-red-100' : 'bg-cyan-50 border-cyan-100'">Rp</div>
                    <input type="text" :inputmode="person.zakat.infaq.metode === 'beras' ? 'decimal' : 'numeric'" x-model="person.zakat.infaq.nominal"
                        @input="person.zakat.infaq.metode !== 'beras' ? person.zakat.infaq.nominal = formatCurrency($event.target.value) : person.zakat.infaq.nominal = formatBeras($event.target.value)"
                        :placeholder="person.zakat.infaq.metode === 'beras' ? '0.00' : '0'" class="flex-1 w-full text-xs border-0 focus:ring-0 py-1.5 px-3 bg-transparent" :required="person.zakat.infaq.active">
                    <div x-show="person.zakat.infaq.metode === 'beras'" class="px-2.5 py-1.5 text-cyan-700 font-black text-[10px] border-l shrink-0" :class="isBelowStandard(person, 'infaq') ? 'bg-red-50 border-red-100' : 'bg-cyan-50 border-cyan-100'">Kg</div>
                </div>
            </div>
        </div>
    </div>
</div>
