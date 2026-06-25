<div class="ui-card relative mb-3 border-slate-200 p-4 transition-all hover:border-brand-200">
    <div class="mb-3 flex flex-col justify-between gap-3 sm:flex-row sm:items-center xl:justify-start">
        <div class="flex items-center gap-2 flex-1 w-full lg:max-w-sm">
            <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand-500 text-xs font-bold text-white sm:h-7 sm:w-7" x-text="index + 1"></div>
            <input type="text" x-model="person.name" class="muzakki-name-input ui-input w-full text-sm" placeholder="Nama..." required>
        </div>
        <button type="button" @click="removePerson(index)" x-show="persons.length > 1" class="ui-btn ui-btn-secondary w-full border-red-100 bg-red-50 py-2 text-xs text-red-500 hover:bg-red-50 hover:text-red-600 sm:w-auto sm:px-3 sm:py-1.5">
            Hapus Baris
        </button>
    </div>

    <div class="grid grid-cols-1 gap-4 border-t border-slate-100 pt-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border p-4 transition-colors" :class="person.zakat.fitrah.active ? 'border-brand-400 bg-brand-50/30 ring-1 ring-brand-300' : 'border-slate-200 bg-slate-50/50'">
            <label class="flex cursor-pointer items-center gap-2 text-sm font-bold text-slate-800">
                <input type="checkbox" x-model="person.zakat.fitrah.active" class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                Zakat Fitrah
            </label>
            <div x-show="person.zakat.fitrah.active" class="mt-3 space-y-3" x-collapse x-transition:enter="transition ease-out duration-300" x-transition:leave="transition ease-in duration-200">
                <div class="space-y-1">
                    <label class="ui-form-label-xs">Bentuk Zakat Fitrah</label>
                    <select x-model="person.zakat.fitrah.metode" class="ui-select w-full text-xs">
                        <option value="uang">Uang (Rp)</option>
                        <option value="beras">Beras (Kg)</option>
                    </select>
                </div>

                <div class="space-y-1">
                    <label class="flex cursor-pointer items-center gap-2 text-[10px] font-bold uppercase tracking-wide text-brand-700">
                        <input type="checkbox" x-model="person.zakat.fitrah.is_custom" class="h-3 w-3 rounded border-slate-300 text-brand-600">
                        Nominal Khusus
                    </label>
                    <p class="text-[10px] text-brand-600/70 leading-tight">Standar: Rp {{ number_format($fitrahUang, 0, ',', '.') }} / {{ $berasPerJiwa }}kg</p>

                    <template x-if="!person.zakat.fitrah.is_custom">
                        <div class="rounded border border-brand-200 bg-brand-100/50 px-2 py-1.5 text-[11px] font-medium not-italic text-brand-800">
                            Sesuai Standar
                        </div>
                    </template>

                    <template x-if="person.zakat.fitrah.is_custom">
                        <div class="mt-1.5 flex w-full items-center overflow-hidden rounded-lg border bg-white transition-all focus-within:ring-1"
                            :class="isBelowStandard(person, 'fitrah') ? 'border-red-500 focus-within:ring-red-500 focus-within:border-red-500' : 'border-brand-300 focus-within:ring-brand-500 focus-within:border-brand-500'">
                            <div x-show="person.zakat.fitrah.metode !== 'beras'" class="shrink-0 border-r px-2.5 py-1.5 text-[10px] font-bold text-brand-700" :class="isBelowStandard(person, 'fitrah') ? 'bg-red-50 border-red-100' : 'bg-brand-50 border-brand-100'">Rp</div>
                            <input type="text" inputmode="decimal" x-model="person.zakat.fitrah.nominal"
                                @input="person.zakat.fitrah.metode !== 'beras' ? person.zakat.fitrah.nominal = formatCurrency($event.target.value) : person.zakat.fitrah.nominal = formatBeras($event.target.value)"
                                :placeholder="person.zakat.fitrah.metode === 'beras' ? 'Isi berat (Kg)...' : '0'"
                                class="w-full flex-1 border-0 bg-transparent px-3 py-1.5 text-xs focus:ring-0">
                            <div x-show="person.zakat.fitrah.metode === 'beras'" class="shrink-0 border-l px-2.5 py-1.5 text-[10px] font-bold text-brand-700" :class="isBelowStandard(person, 'fitrah') ? 'bg-red-50 border-red-100' : 'bg-brand-50 border-brand-100'">Kg</div>
                        </div>
                    </template>
                </div>

            </div>
        </div>

        <div class="rounded-xl border p-3 transition-colors" :class="person.zakat.fidyah.active ? 'border-amber-400 bg-amber-50/30 ring-1 ring-amber-300' : 'border-slate-200 bg-slate-50/50'">
            <label class="flex cursor-pointer items-center gap-2 text-sm font-bold text-slate-800">
                <input type="checkbox" x-model="person.zakat.fidyah.active" class="h-4 w-4 rounded border-slate-300 text-amber-600 focus:ring-amber-500">
                Fidyah
            </label>
            <div x-show="person.zakat.fidyah.active" class="mt-3 space-y-3" x-collapse>
                <div class="flex items-center overflow-hidden rounded-lg border border-slate-200 bg-white focus-within:border-amber-500 focus-within:ring-1 focus-within:ring-amber-500">
                    <input type="number" min="1" x-model="person.zakat.fidyah.hari" placeholder="Jml. Hari" class="w-full text-xs border-0 focus:ring-0" :required="person.zakat.fidyah.active">
                    <span class="border-l border-slate-200 bg-slate-50 px-2 text-xs font-medium text-slate-500">Hari</span>
                </div>
                <div class="space-y-1">
                    <label class="ui-form-label-xs">Bentuk Fidyah</label>
                    <select x-model="person.zakat.fidyah.metode" class="ui-select w-full text-xs">
                        <option value="uang">Uang (Rp)</option>
                        <option value="beras">Beras (Kg)</option>
                    </select>
                </div>

                <div class="space-y-1">
                    <label class="flex items-center gap-2 cursor-pointer text-[10px] font-bold text-amber-700 uppercase tracking-wider">
                        <input type="checkbox" x-model="person.zakat.fidyah.is_custom" class="h-3 w-3 rounded border-slate-300 text-amber-600">
                        Nominal Khusus
                    </label>
                    <p class="text-[10px] text-amber-600/70 leading-tight">Standar: Rp {{ number_format($fidyahUang, 0, ',', '.') }} / {{ $berasPerJiwa }}kg per hari</p>

                    <template x-if="!person.zakat.fidyah.is_custom">
                        <div class="text-[11px] bg-amber-100/50 text-amber-800 px-2 py-1.5 rounded border border-amber-200 font-medium not-italic">
                            Sesuai Standar
                        </div>
                    </template>

                    <template x-if="person.zakat.fidyah.is_custom">
                        <div class="mt-1.5 flex w-full items-center overflow-hidden rounded-lg border bg-white transition-all focus-within:ring-1"
                            :class="isBelowStandard(person, 'fidyah') ? 'border-red-500 focus-within:ring-red-500 focus-within:border-red-500' : 'border-amber-300 focus-within:ring-amber-500 focus-within:border-amber-500'">
                            <div x-show="person.zakat.fidyah.metode !== 'beras'" class="shrink-0 border-r px-2.5 py-1.5 text-[10px] font-bold text-amber-700" :class="isBelowStandard(person, 'fidyah') ? 'bg-red-50 border-red-100' : 'bg-amber-50 border-amber-100'">Rp</div>
                            <input type="text" inputmode="decimal" x-model="person.zakat.fidyah.nominal"
                                @input="person.zakat.fidyah.metode !== 'beras' ? person.zakat.fidyah.nominal = formatCurrency($event.target.value) : person.zakat.fidyah.nominal = formatBeras($event.target.value)"
                                :placeholder="person.zakat.fidyah.metode === 'beras' ? 'Isi berat (Kg)...' : '0'"
                                class="w-full flex-1 border-0 bg-transparent px-3 py-1.5 text-xs focus:ring-0">
                            <div x-show="person.zakat.fidyah.metode === 'beras'" class="shrink-0 border-l px-2.5 py-1.5 text-[10px] font-bold text-amber-700" :class="isBelowStandard(person, 'fidyah') ? 'bg-red-50 border-red-100' : 'bg-amber-50 border-amber-100'">Kg</div>
                        </div>
                    </template>
                </div>

            </div>
        </div>

        <div class="rounded-xl border p-3 transition-colors" :class="person.zakat.mal.active ? 'border-blue-400 bg-blue-50/30 ring-1 ring-blue-300' : 'border-slate-200 bg-slate-50/50'">
            <label class="flex cursor-pointer items-center gap-2 text-sm font-bold text-slate-800">
                <input type="checkbox" x-model="person.zakat.mal.active" class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                Zakat Mal
            </label>
            <div x-show="person.zakat.mal.active" class="mt-3 space-y-2" x-collapse>
                <div class="space-y-1">
                    <label class="ui-form-label-xs">Bentuk Zakat Mal</label>
                    <select x-model="person.zakat.mal.metode" class="ui-select w-full text-xs">
                        <option value="uang">Uang (Rp)</option>
                        <option value="beras">Beras (Kg)</option>
                    </select>
                </div>
                <div class="mt-1.5 flex w-full items-center overflow-hidden rounded-lg border bg-white transition-all focus-within:ring-1"
                    :class="isBelowStandard(person, 'mal') ? 'border-red-500 focus-within:ring-red-500 focus-within:border-red-500' : 'border-slate-300 focus-within:ring-blue-500 focus-within:border-blue-500'">
                    <div x-show="person.zakat.mal.metode !== 'beras'" class="shrink-0 border-r px-2.5 py-1.5 text-[10px] font-bold text-blue-700" :class="isBelowStandard(person, 'mal') ? 'bg-red-50 border-red-100' : 'bg-blue-50 border-blue-100'">Rp</div>
                    <input type="text" :inputmode="person.zakat.mal.metode === 'beras' ? 'decimal' : 'numeric'" x-model="person.zakat.mal.nominal"
                        @input="person.zakat.mal.metode !== 'beras' ? person.zakat.mal.nominal = formatCurrency($event.target.value) : person.zakat.mal.nominal = formatBeras($event.target.value)"
                        :placeholder="person.zakat.mal.metode === 'beras' ? '0.00' : '0'" class="w-full flex-1 border-0 bg-transparent px-3 py-1.5 text-xs focus:ring-0" :required="person.zakat.mal.active">
                    <div x-show="person.zakat.mal.metode === 'beras'" class="shrink-0 border-l px-2.5 py-1.5 text-[10px] font-bold text-blue-700" :class="isBelowStandard(person, 'mal') ? 'bg-red-50 border-red-100' : 'bg-blue-50 border-blue-100'">Kg</div>
                </div>
            </div>
        </div>

        <div class="rounded-xl border p-3 transition-colors" :class="person.zakat.infaq.active ? 'border-cyan-400 bg-cyan-50/40 ring-1 ring-cyan-300' : 'border-slate-200 bg-slate-50/50'">
            <label class="flex cursor-pointer items-center gap-2 text-sm font-bold text-slate-800">
                <input type="checkbox" x-model="person.zakat.infaq.active" class="h-4 w-4 rounded border-slate-300 text-cyan-600 focus:ring-cyan-500">
                Infaq Sedekah
            </label>
            <div x-show="person.zakat.infaq.active" class="mt-3 space-y-2" x-collapse>
                <div class="space-y-1">
                    <label class="ui-form-label-xs">Bentuk Infaq Sedekah</label>
                    <select x-model="person.zakat.infaq.metode" class="ui-select w-full text-xs">
                        <option value="uang">Uang (Rp)</option>
                        <option value="beras">Beras (Kg)</option>
                    </select>
                </div>
                <div class="mt-1.5 flex w-full items-center overflow-hidden rounded-lg border bg-white transition-all focus-within:ring-1"
                    :class="isBelowStandard(person, 'infaq') ? 'border-red-500 focus-within:ring-red-500 focus-within:border-red-500' : 'border-cyan-300 focus-within:ring-cyan-500 focus-within:border-cyan-500'">
                    <div x-show="person.zakat.infaq.metode !== 'beras'" class="shrink-0 border-r px-2.5 py-1.5 text-[10px] font-bold text-cyan-700" :class="isBelowStandard(person, 'infaq') ? 'bg-red-50 border-red-100' : 'bg-cyan-50 border-cyan-100'">Rp</div>
                    <input type="text" :inputmode="person.zakat.infaq.metode === 'beras' ? 'decimal' : 'numeric'" x-model="person.zakat.infaq.nominal"
                        @input="person.zakat.infaq.metode !== 'beras' ? person.zakat.infaq.nominal = formatCurrency($event.target.value) : person.zakat.infaq.nominal = formatBeras($event.target.value)"
                        :placeholder="person.zakat.infaq.metode === 'beras' ? '0.00' : '0'" class="w-full flex-1 border-0 bg-transparent px-3 py-1.5 text-xs focus:ring-0" :required="person.zakat.infaq.active">
                    <div x-show="person.zakat.infaq.metode === 'beras'" class="shrink-0 border-l px-2.5 py-1.5 text-[10px] font-bold text-cyan-700" :class="isBelowStandard(person, 'infaq') ? 'bg-red-50 border-red-100' : 'bg-cyan-50 border-cyan-100'">Kg</div>
                </div>
            </div>
        </div>
    </div>
</div>
