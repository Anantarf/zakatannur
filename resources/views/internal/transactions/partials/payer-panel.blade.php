<div class="ui-card overflow-hidden">
    <div class="flex items-center gap-2 border-b border-blue-100 bg-blue-50 px-5 py-3">
        <svg class="h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
        </svg>
        <h3 class="font-bold text-blue-900">Pembayar</h3>
    </div>
    <div class="space-y-4 p-4">
        @if(isset($isEdit))
            <div class="mb-2 rounded-xl border border-blue-400 bg-blue-600 p-3 text-white shadow-sm">
                <p class="mb-0.5 text-[10px] font-bold uppercase leading-none tracking-[0.14em] opacity-80">Sedang Mengedit:</p>
                <p class="text-sm font-bold truncate tracking-tight">{{ $mainTx->pembayar_nama }}</p>
            </div>
        @endif

        <input type="hidden" name="tahun_zakat" value="{{ $activeYear }}">

        <div>
            <label class="ui-form-label">Shift Petugas <span class="text-red-500">*</span></label>
            <select name="shift" x-model="shift" class="ui-select w-full" required>
                <option value="">-- Pilih Shift --</option>
                @foreach($shifts as $shift)
                    <option value="{{ $shift }}" {{ (old('shift', $mainTx->shift ?? (\Carbon\Carbon::now('Asia/Jakarta')->hour < 14 ? 'pagi' : (\Carbon\Carbon::now('Asia/Jakarta')->hour < 18 ? 'siang' : 'malam'))) == $shift) ? 'selected' : '' }}>
                        {{ $shiftLabels[$shift] }}
                    </option>
                @endforeach
            </select>
        </div>

        @if(isset($isEdit))
            <div>
                <label class="ui-form-label">Petugas Pelayan (Data Asli)</label>
                <div class="relative">
                    <select disabled class="w-full cursor-not-allowed rounded-lg border-slate-200 bg-slate-50 text-sm text-slate-500 shadow-sm">
                        @foreach($officers as $off)
                            <option value="{{ $off->id }}" {{ (old('petugas_id', $mainTx->petugas_id) == $off->id) ? 'selected' : '' }}>
                                {{ $off->name }} ({{ strtoupper($off->role) }})
                            </option>
                        @endforeach
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                        <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                    </div>
                </div>
            </div>
            <div>
                <label class="ui-form-label">Waktu Transaksi (Asal)</label>
                <div class="relative">
                    <input type="datetime-local" readonly
                        value="{{ old('waktu_terima', ($mainTx->waktu_terima ?? $mainTx->created_at)->timezone('Asia/Jakarta')->format('Y-m-d\TH:i')) }}"
                        class="pointer-events-none w-full cursor-not-allowed rounded-lg border-slate-200 bg-slate-50 text-sm text-slate-500 shadow-sm">
                    <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                        <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                </div>
                <div class="mt-1.5 flex items-center gap-1.5 rounded-md border border-blue-100 bg-blue-50 px-2 py-1">
                    <svg class="h-3 w-3 text-blue-600 animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <p class="text-[10px] font-bold text-blue-700 tracking-tight">Akan diperbarui otomatis ke waktu sekarang</p>
                </div>
            </div>
        @endif

        <div>
            <label class="ui-form-label">Nama Pembayar <span class="text-red-500">*</span></label>
            <div class="relative" @click.away="showSuggestions = false">
                <input type="text" name="pembayar_nama" x-model="pembayar_name"
                    @input="handleInput()"
                    @keydown.arrow-down.prevent="selectNext()"
                    @keydown.arrow-up.prevent="selectPrev()"
                    @keydown.enter.prevent="selectActive()"
                    class="ui-input w-full"
                    autocomplete="off"
                    required placeholder="Ketik nama...">
                <div class="mt-1.5 flex items-center">
                    <button type="button" @click="pembayar_name = 'Hamba Allah'; pembayar_address = '-'; handleInput(); showSuggestions = false"
                        class="rounded-full border border-slate-200 bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-500 transition-colors hover:bg-brand-500 hover:text-white">
                        Set Hamba Allah
                    </button>
                </div>

                <div x-show="showSuggestions && suggestions.length > 0"
                    class="absolute z-50 mt-1 w-full overflow-hidden rounded-xl border border-blue-100 bg-white shadow-lg"
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="opacity-0 transform scale-95"
                    x-transition:enter-end="opacity-100 transform scale-100">
                    <template x-for="(s, i) in suggestions" :key="s.id">
                        <div @click="selectSuggestion(s)"
                            @mouseover="activeIndex = i"
                            :class="{'bg-blue-50 text-blue-900': activeIndex === i, 'text-slate-700': activeIndex !== i}"
                            class="cursor-pointer border-b border-slate-50 px-4 py-3 transition-colors last:border-0 hover:bg-blue-50">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-sm" x-html="jsHighlight(s.name, pembayar_name)"></span>
                                <span class="text-[10px] font-bold text-blue-500 bg-blue-100 px-2 py-0.5 rounded-full" x-show="activeIndex === i">Pilih</span>
                            </div>
                            <div class="mt-1 flex items-center gap-1 text-[11px] text-slate-500">
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                <span x-html="jsHighlight(s.address || 'Alamat tidak ada', pembayar_name)"></span>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <div>
            <label class="ui-form-label">Alamat Domisili <span class="text-red-500">*</span></label>
            <textarea name="pembayar_alamat" rows="3" x-model="pembayar_address" class="ui-textarea w-full" required placeholder="Wajib diisi..."></textarea>
        </div>

        <div>
            <label class="ui-form-label">Nomor HP / WhatsApp (Opsional)</label>
            <input type="text" name="pembayar_phone" x-model="pembayar_phone" placeholder="08xxx" class="ui-input w-full">
        </div>
    </div>
</div>
