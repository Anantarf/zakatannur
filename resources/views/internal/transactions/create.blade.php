<x-app-layout>
    <style>
        [x-cloak] { display: none !important; }
    </style>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <h2 class="font-bold text-xl sm:text-2xl text-emerald-800 leading-tight text-center sm:text-left">
                {{ isset($isEdit) ? 'Edit Transaksi ' . $mainTx->no_transaksi : 'Input Transaksi' }}
            </h2>
            <div class="inline-flex items-center justify-center bg-emerald-100 text-emerald-800 px-3 py-1.5 rounded-xl font-black text-[10px] tracking-widest uppercase shadow-sm w-fit mx-auto sm:mx-0">
                TAHUN: {{ $activeYear }}
            </div>
        </div>
    </x-slot>

    <div class="py-12" x-data="zakatForm()">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            @if ($errors->any())
                <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-5 text-red-900 shadow-sm">
                    <div class="font-bold text-red-800 mb-3 border-b border-red-200 pb-2">Terdapat Kesalahan Input:</div>
                    <ul class="list-disc pl-5 text-sm space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ isset($isEdit) ? route('internal.transactions.update', ['transaction' => $mainTx->id]) : route('internal.transactions.store') }}" @submit="prepareSubmit" class="space-y-6">
                @csrf
                @if(isset($isEdit))
                    @method('PATCH')
                @endif

                <!-- Render Hidden Inputs dynamically from Alpine txs -->
                <template x-for="(tx, i) in txs" :key="i">
                    <div style="display:none;">
                        <input type="hidden" :name="`items[${i}][muzakki_name]`" :value="tx.muzakki_name">
                        <input type="hidden" :name="`items[${i}][category]`" :value="tx.category">
                        <input type="hidden" :name="`items[${i}][metode]`" :value="tx.metode">
                        <template x-if="tx.category === 'fitrah'">
                            <input type="hidden" :name="`items[${i}][jiwa]`" value="1">
                        </template>
                        <template x-if="tx.hari">
                            <input type="hidden" :name="`items[${i}][hari]`" :value="tx.hari">
                        </template>
                        <template x-if="tx.nominal_uang !== null && tx.nominal_uang !== ''">
                            <input type="hidden" :name="`items[${i}][nominal_uang]`" :value="tx.nominal_uang">
                        </template>
                        <template x-if="tx.jumlah_beras_kg !== null && tx.jumlah_beras_kg !== ''">
                            <input type="hidden" :name="`items[${i}][jumlah_beras_kg]`" :value="tx.jumlah_beras_kg">
                        </template>
                        <template x-if="tx.id">
                            <input type="hidden" :name="`items[${i}][id]`" :value="tx.id">
                        </template>
                        <template x-if="tx.is_custom">
                            <input type="hidden" :name="`items[${i}][is_custom]`" value="1">
                        </template>
                        <template x-if="tx.is_transfer">
                            <input type="hidden" :name="`items[${i}][is_transfer]`" value="1">
                        </template>
                    </div>
                </template>

                <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                    
                    <!-- Left Sidebar (Global Payer) -->
                    <div class="lg:col-span-1 space-y-6">
                        <div class="bg-white rounded-2xl shadow-md border border-gray-100 overflow-hidden">
                            <div class="bg-blue-50 px-5 py-3 border-b border-blue-100 flex items-center gap-2">
                                <svg class="h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                </svg>
                                <h3 class="font-bold text-blue-900">Pembayar</h3>
                            </div>
                            <div class="p-5 space-y-4">
                                @if(isset($isEdit))
                                    <div class="bg-blue-600 rounded-xl p-3 text-white shadow-sm mb-2 border border-blue-400">
                                        <p class="text-[10px] uppercase font-black tracking-widest opacity-80 mb-0.5 leading-none">Sedang Mengedit Milik:</p>
                                        <p class="text-sm font-bold truncate tracking-tight">{{ $mainTx->pembayar_nama }}</p>
                                    </div>
                                @endif
                                <!-- Hidden Year -->
                                <input type="hidden" name="tahun_zakat" value="{{ $activeYear }}">
                                
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Shift Petugas <span class="text-red-500">*</span></label>
                                    <select name="shift" x-model="shift" class="w-full text-sm rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
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
                                        <label class="block text-xs font-bold text-gray-700 mb-1">Petugas Pelayan (Data Asli)</label>
                                        <div class="relative">
                                            <select disabled class="w-full text-sm rounded-lg border-gray-200 bg-gray-50 text-gray-500 shadow-sm cursor-not-allowed">
                                                @foreach($officers as $off)
                                                    <option value="{{ $off->id }}" {{ (old('petugas_id', $mainTx->petugas_id) == $off->id) ? 'selected' : '' }}>
                                                        {{ $off->name }} ({{ strtoupper($off->role) }})
                                                    </option>
                                                @endforeach
                                            </select>
                                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                                <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 mb-1">Waktu Transaksi (Asal)</label>
                                        <div class="relative">
                                            <input type="datetime-local" readonly
                                                   value="{{ old('waktu_terima', ($mainTx->waktu_terima ?? $mainTx->created_at)->timezone('Asia/Jakarta')->format('Y-m-d\TH:i')) }}" 
                                                   class="w-full text-sm rounded-lg border-gray-200 bg-gray-50 text-gray-500 shadow-sm cursor-not-allowed pointer-events-none">
                                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                                <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                            </div>
                                        </div>
                                        <div class="mt-1.5 flex items-center gap-1.5 px-2 py-1 bg-blue-50 border border-blue-100 rounded-md">
                                            <svg class="h-3 w-3 text-blue-600 animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                            <p class="text-[10px] font-bold text-blue-700 tracking-tight">Akan diperbarui otomatis ke waktu sekarang</p>
                                        </div>
                                    </div>
                                @endif
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Nama Pembayar <span class="text-red-500">*</span></label>
                                    <div class="relative" @click.away="showSuggestions = false">
                                        <input type="text" name="pembayar_nama" x-model="pembayar_name" 
                                               @input="handleInput()" 
                                               @keydown.arrow-down.prevent="selectNext()"
                                               @keydown.arrow-up.prevent="selectPrev()"
                                               @keydown.enter.prevent="selectActive()"
                                               class="w-full text-sm rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                                               autocomplete="off"
                                               required placeholder="Ketik nama...">
                                        <div class="mt-1.5 flex items-center">
                                            <button type="button" @click="pembayar_name = 'Hamba Allah'; pembayar_address = '-'; handleInput(); showSuggestions = false" 
                                                    class="text-[10px] font-bold bg-gray-100 text-gray-500 hover:bg-emerald-500 hover:text-white px-2 py-0.5 rounded-full transition-colors border border-gray-200">
                                                Set Hamba Allah
                                            </button>
                                        </div>
                                        
                                        <!-- Suggestions Dropdown -->
                                        <div x-show="showSuggestions && suggestions.length > 0" 
                                             class="absolute z-50 mt-1 w-full bg-white rounded-xl shadow-2xl border border-blue-100 overflow-hidden"
                                             x-transition:enter="transition ease-out duration-100"
                                             x-transition:enter-start="opacity-0 transform scale-95"
                                             x-transition:enter-end="opacity-100 transform scale-100">
                                            <template x-for="(s, i) in suggestions" :key="s.id">
                                                <div @click="selectSuggestion(s)" 
                                                     @mouseover="activeIndex = i"
                                                     :class="{'bg-blue-50 text-blue-900': activeIndex === i, 'text-gray-700': activeIndex !== i}"
                                                     class="px-4 py-3 cursor-pointer border-b border-gray-50 last:border-0 hover:bg-blue-50 transition-colors">
                                                    <div class="flex items-center justify-between">
                                                        <span class="font-bold text-sm" x-html="jsHighlight(s.name, pembayar_name)"></span>
                                                        <span class="text-[10px] font-bold text-blue-500 bg-blue-100 px-2 py-0.5 rounded-full" x-show="activeIndex === i">Pilih</span>
                                                    </div>
                                                    <div class="text-[11px] text-gray-500 mt-1 flex items-center gap-1">
                                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                                        <span x-html="jsHighlight(s.address || 'Alamat tidak ada', pembayar_name)"></span>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Alamat Domisili <span class="text-red-500">*</span></label>
                                    <textarea name="pembayar_alamat" rows="3" x-model="pembayar_address" class="w-full text-sm rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required placeholder="Wajib diisi..."></textarea>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Nomor HP / WhatsApp (Opsional)</label>
                                    <input type="text" name="pembayar_phone" x-model="pembayar_phone" placeholder="08xxx" class="w-full text-sm rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </div>
                            </div>
                        </div>

                        <!-- Transfer Option Box -->
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
                    </div>

                    <!-- Right Column (Matrix Members) -->
                    <div class="lg:col-span-3 space-y-4 max-w-full">
                        <div class="bg-white p-4 sm:p-5 rounded-2xl shadow-sm border border-gray-100">
                            <h3 class="font-bold text-xl text-gray-800">Detail Pembayaran</h3>
                            <p class="text-sm text-gray-500 mt-1">Satu baris mewakili satu jiwa/nama. Centang untuk memilih lebih dari 1 jenis zakat (Fitrah, Fidyah, Mal, dll.)</p>
                        </div>

                        <template x-for="(person, index) in persons" :key="person.id">
                            <div class="bg-white border sm:rounded-2xl p-4 sm:p-5 shadow-sm border-gray-200 relative mb-4 transition-all hover:border-emerald-200">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between xl:justify-start gap-3 mb-4">
                                    <div class="flex items-center gap-2 flex-1 w-full lg:max-w-sm">
                                        <div class="h-6 w-6 sm:h-7 sm:w-7 bg-emerald-500 text-white rounded-full flex justify-center items-center text-xs font-bold shadow-sm shrink-0" x-text="index + 1"></div>
                                        <input type="text" x-model="person.name" class="muzakki-name-input w-full text-sm rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500" placeholder="Nama..." required>
                                    </div>
                                    <button type="button" @click="removePerson(index)" x-show="persons.length > 1" class="w-full sm:w-auto text-red-400 hover:text-red-600 bg-red-50 sm:px-3 py-2 sm:py-1.5 rounded-lg text-xs font-bold transition-colors border border-transparent hover:border-red-100 text-center">
                                        Hapus Baris
                                    </button>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 border-t border-gray-100 pt-4">
                                    <!-- Fitrah -->
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

                                    <!-- Fidyah -->
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

                                    <!-- Mal -->
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

                                    <!-- Infaq -->
                                    <div class="rounded-xl border p-3 transition-colors" :class="person.zakat.infaq.active ? 'border-purple-400 bg-purple-50/30 ring-1 ring-purple-400' : 'border-gray-200 bg-gray-50/50'">
                                        <label class="flex items-center gap-2 cursor-pointer font-bold text-gray-800 text-sm">
                                            <input type="checkbox" x-model="person.zakat.infaq.active" class="h-4 w-4 text-purple-600 border-gray-300 rounded focus:ring-purple-500">
                                            Infaq Sedekah
                                        </label>
                                        <div x-show="person.zakat.infaq.active" class="mt-3 space-y-2" x-collapse>
                                            <div class="space-y-1">
                                                <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Bentuk Infaq Sedekah</label>
                                                <select x-model="person.zakat.infaq.metode" class="w-full text-xs rounded-lg border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
                                                    <option value="uang">Uang (Rp)</option>
                                                    <option value="beras">Beras (Kg)</option>
                                                </select>
                                            </div>
                                            <div class="flex items-center w-full rounded-lg border overflow-hidden shadow-sm focus-within:ring-1 bg-white mt-1.5 transition-all"
                                                :class="isBelowStandard(person, 'infaq') ? 'border-red-500 focus-within:ring-red-500 focus-within:border-red-500' : 'border-purple-300 focus-within:ring-purple-500 focus-within:border-purple-500'">
                                                 <div x-show="person.zakat.infaq.metode !== 'beras'" class="px-2.5 py-1.5 text-purple-700 font-black text-[10px] border-r shrink-0" :class="isBelowStandard(person, 'infaq') ? 'bg-red-50 border-red-100' : 'bg-purple-50 border-purple-100'">Rp</div>
                                                 <input type="text" :inputmode="person.zakat.infaq.metode === 'beras' ? 'decimal' : 'numeric'" x-model="person.zakat.infaq.nominal" 
                                                        @input="person.zakat.infaq.metode !== 'beras' ? person.zakat.infaq.nominal = formatCurrency($event.target.value) : person.zakat.infaq.nominal = formatBeras($event.target.value)"
                                                        :placeholder="person.zakat.infaq.metode === 'beras' ? '0.00' : '0'" class="flex-1 w-full text-xs border-0 focus:ring-0 py-1.5 px-3 bg-transparent" :required="person.zakat.infaq.active">
                                                 <div x-show="person.zakat.infaq.metode === 'beras'" class="px-2.5 py-1.5 text-purple-700 font-black text-[10px] border-l shrink-0" :class="isBelowStandard(person, 'infaq') ? 'bg-red-50 border-red-100' : 'bg-purple-50 border-purple-100'">Kg</div>
                                             </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                        
                        <!-- Button Tambah Orang di Bawah Box Terakhir -->
                        <div class="pb-4">
                            <button type="button" @click="addPerson()" class="w-full bg-emerald-50 text-emerald-700 hover:bg-emerald-100 px-4 py-4 rounded-2xl font-bold flex items-center justify-center gap-2 transition-all border-2 border-dashed border-emerald-200 hover:border-emerald-300 group">
                                <div class="bg-emerald-500 text-white rounded-full p-1 group-hover:scale-110 transition-transform">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4" />
                                    </svg>
                                </div>
                                Tambah Orang / Anggota Keluarga
                            </button>
                        </div>

                        <!-- Error Message Container if empty txs -->
                        <div x-show="txs.length === 0" class="text-sm text-red-600 font-bold bg-red-50 p-3 rounded-lg border border-red-200">
                            Harap pilih minimal 1 jenis zakat (Fitrah, Fidyah, Mal, atau Infaq) untuk muzakki.
                        </div>
                        
                        <!-- Realtime Info Badge -->
                        <div class="flex justify-end hidden">
                            <span class="text-sm font-bold text-gray-500 bg-gray-100 px-3 py-1 rounded-full border border-gray-200" x-text="'Total Transaksi Dipilih: '+txs.length"></span>
                        </div>

                         <div class="sticky bottom-4 z-10 pt-4 mt-6 bg-white/95 backdrop-blur-md p-4 rounded-2xl shadow-xl border border-emerald-100">
                             @if(isset($isEdit))
                                 <div class="flex items-stretch gap-3">
                                     <a href="{{ route('internal.transactions.index') }}" 
                                        class="flex-1 flex justify-center items-center gap-2 rounded-xl bg-slate-100 px-6 py-4 text-sm font-bold text-slate-600 hover:bg-slate-200 transition-all active:scale-[0.98]">
                                         Kembali
                                     </a>
                                     <x-emerald-button 
                                             :disabled="submitting || !hasChanged"
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
                                         :disabled="submitting"
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
                    </div>
                </div>
            </form>

    <!-- Modal Transfer -->
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
</div>
</div>

    <!-- AlpineJS Component Logic -->
    <script>
        function zakatForm() {
            return {
                isEdit: {{ isset($isEdit) ? 'true' : 'false' }},
                pembayar_name: @json(old('pembayar_nama', $mainTx->pembayar_nama ?? '')),
                pembayar_address: @json(old('pembayar_alamat', $mainTx->pembayar_alamat ?? '')),
                pembayar_phone: @json(old('pembayar_phone', $mainTx->pembayar_phone ?? '')),
                
                // Header fields
                shift: @json(old('shift', $mainTx->shift ?? '')),
                petugas_id: @json(old('petugas_id', $mainTx->petugas_id ?? '')),
                waktu_terima: @json(old('waktu_terima', isset($mainTx) ? ($mainTx->waktu_terima ?? $mainTx->created_at)->timezone('Asia/Jakarta')->format('Y-m-d\TH:i') : '')),

                submitting: false,
                is_transfer_global: false,
                show_tf_modal: false,
                initialSnapshot: null,
                lastPembayarName: @json(old('pembayar_nama', $mainTx->pembayar_nama ?? '')),
                
                // Standard nominals from PHP
                fitrahBase: @json($fitrahUang),
                fidyahBase: @json($fidyahUang),
                malBase: 0,
                infaqBase: 0,

                getSnapshot: function() {
                    return JSON.stringify({
                        p: this.pembayar_name,
                        a: this.pembayar_address,
                        ph: this.pembayar_phone,
                        s: this.shift,
                        tg: this.is_transfer_global,
                        txs: this.txs
                    });
                },

                get hasChanged() {
                    if (!this.isEdit) return true;
                    return this.initialSnapshot !== this.getSnapshot();
                },
                
                suggestions: [],
                showSuggestions: false,
                activeIndex: -1,
                
                getEffectiveNominal(person, cat) {
                    let z = person.zakat[cat];
                    if (z.is_custom) return z.nominal || '0';
                    
                    if (cat === 'fitrah') return (this.fitrahBase * 1).toLocaleString('id-ID');
                    if (cat === 'fidyah') return (this.fidyahBase * (person.hari || 0)).toLocaleString('id-ID');
                    
                    return z.nominal || '0';
                },

                openTfModal() {
                    // Validasi nama menggunakan reportValidity agar muncul tooltip browser sesuai request
                    let inputs = document.querySelectorAll('.muzakki-name-input');
                    let firstInvalid = null;
                    
                    this.persons.forEach((p, idx) => {
                        if (p.name.trim() === '' && inputs[idx]) {
                            if (!firstInvalid) firstInvalid = inputs[idx];
                        }
                    });

                    if (firstInvalid) {
                        firstInvalid.reportValidity();
                        firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        return;
                    }
                    this.show_tf_modal = true;
                },

                searchTimeout: null,
                async handleInput() {
                    this.syncFirstName();
                    
                    if (this.pembayar_name.length < 2) {
                        this.suggestions = [];
                        this.showSuggestions = false;
                        return;
                    }

                    // Debounce fetch calls
                    clearTimeout(this.searchTimeout);
                    this.searchTimeout = setTimeout(async () => {
                        try {
                            let response = await fetch(`{{ route('internal.muzakki.autocomplete') }}?q=${encodeURIComponent(this.pembayar_name)}`);
                            this.suggestions = await response.json();
                            this.showSuggestions = this.suggestions.length > 0;
                            this.activeIndex = -1;
                        } catch (e) {
                            console.error('Autocomplete error:', e);
                        }
                    }, 300);
                },

                selectSuggestion(s) {
                    this.pembayar_name = s.name;
                    this.pembayar_address = s.address || '';
                    this.pembayar_phone = s.phone || '';
                    this.showSuggestions = false;
                    this.syncFirstName();
                },

                selectNext() {
                    if (this.suggestions.length > 0) {
                        this.activeIndex = (this.activeIndex + 1) % this.suggestions.length;
                    }
                },

                selectPrev() {
                    if (this.suggestions.length > 0) {
                        this.activeIndex = (this.activeIndex - 1 + this.suggestions.length) % this.suggestions.length;
                    }
                },

                selectActive() {
                    if (this.activeIndex >= 0 && this.activeIndex < this.suggestions.length) {
                        this.selectSuggestion(this.suggestions[this.activeIndex]);
                    }
                },

            @php
                $initialPersons = old('persons', $persons ?? [
                    [
                        'id' => 1,
                        'name' => '',
                        'zakat' => [
                            'fitrah' => ['active' => false, 'metode' => 'uang', 'is_custom' => false, 'is_transfer' => false, 'nominal' => ''],
                            'fidyah' => ['active' => false, 'metode' => 'uang', 'is_custom' => false, 'is_transfer' => false, 'hari' => '', 'nominal' => ''],
                            'mal' => ['active' => false, 'metode' => 'uang', 'is_transfer' => false, 'nominal' => ''],
                            'infaq' => ['active' => false, 'metode' => 'uang', 'is_transfer' => false, 'nominal' => '']
                        ]
                    ]
                ]);
            @endphp

                persons: @json($initialPersons),

                // Live search highlighting in JS
                jsHighlight(text, query) {
                    if (!query || query.trim() === '') return this.escapeHtml(text);
                    const escapedText = this.escapeHtml(text);
                    // Escape query for use in Regex
                    const escapedQuery = query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                    const regex = new RegExp(`(${escapedQuery})`, 'gi');
                    return escapedText.replace(regex, '<mark class="bg-yellow-200 font-bold text-gray-900 rounded-px px-0.5">$1</mark>');
                },

                escapeHtml(text) {
                    const div = document.createElement('div');
                    div.textContent = text;
                    return div.innerHTML;
                },

                // Helper to format currency (1000 -> 1,000)
                formatCurrency(val) {
                    if (!val) return '';
                    let s = String(val).replace(/[^0-9]/g, '');
                    if (s === '') return '';
                    return new Intl.NumberFormat('id-ID').format(parseInt(s));
                },

                // Helper to format rice (2,5 -> 2.5) with max 2 decimal places
                formatBeras(val) {
                    if (!val) return '';
                    let s = String(val).replace(/,/g, '.');
                    // Allow only numbers and one dot
                    s = s.replace(/[^0-9.]/g, '');
                    const parts = s.split('.');
                    if (parts.length > 2) {
                        s = parts[0] + '.' + parts.slice(1).join('');
                    }
                    if (parts.length > 1 && parts[1].length > 2) {
                        s = parts[0] + '.' + parts[1].substring(0, 2);
                    }
                    return s;
                },

                // Helper to parse input (2,5 -> 2.5 and 1.000 -> 1000)
                // isBeras: boolean, jika true maka titik dianggap desimal (2.5 -> 2.5) bukan ribuan.
                parseNum(val, isBeras = false) {
                    if (val === null || val === undefined || val === '') return null;
                    let s = String(val).trim().replace(/[Rp\s]/gi, '').replace(/kg/gi, '');
                    
                    if (isBeras) {
                        // Untuk beras, titik (.) atau koma (,) hampir pasti desimal (2.5 atau 2,5)
                        s = s.replace(',', '.');
                    } else {
                        // Untuk uang, titik ribuan dibuang (1.000 -> 1000), koma jadi desimal
                        // Karena kita menggunakan format id-ID (titik ribuan), kita hapus titik
                        s = s.replace(/\./g, '').replace(',', '.');
                    }
                    
                    let n = parseFloat(s);
                    return isNaN(n) ? null : n;
                },

                // Konfigurasi Standar untuk Validasi Visual
                standards: {
                    fitrahUang: {{ $fitrahUang }},
                    fidyahUang: {{ $fidyahUang }},
                    fidyahBeras: {{ $fidyahBeras }},
                    beras: {{ $berasPerJiwa }}
                },

                isBelowStandard(person, cat) {
                    let z = person.zakat[cat];
                    if (!z.active) return false;
                    
                    let isBeras = z.metode === 'beras';
                    let val = this.parseNum(z.nominal, isBeras);
                    
                    // Mal & Infaq: Hanya cek apakah kosong/0
                    if (cat === 'mal' || cat === 'infaq') {
                        return val === null || val <= 0;
                    }

                    if (!z.is_custom) return false;
                    
                    // Regulasi: Jika Nominal Khusus Aktif, WAJIB LEBIH BESAR (>) dari Standar
                    // Jika null, 0, atau sama dengan standar, maka dianggap Invalid (Merah)
                    if (val === null || val <= 0) return true;

                    if (cat === 'fitrah') {
                        let std = isBeras ? this.standards.beras : this.standards.fitrahUang;
                        return val <= std; // Harus > std, jadi <= std adalah error/merah
                    }
                    if (cat === 'fidyah') {
                        let hari = parseInt(z.hari) || 0;
                        if (hari <= 0) return true;
                        let stdPerHari = isBeras ? this.standards.fidyahBeras : this.standards.fidyahUang; 
                        return val <= (stdPerHari * hari); // Harus > total standar hari
                    }
                    return false;
                },

                handleTfGlobalChange() {
                    if (this.is_transfer_global) {
                        // Validasi nama dlu sebelum aktifkan TF Global
                        let inputs = document.querySelectorAll('.muzakki-name-input');
                        let firstInvalid = null;
                        this.persons.forEach((p, idx) => {
                            if (p.name.trim() === '' && inputs[idx]) {
                                if (!firstInvalid) firstInvalid = inputs[idx];
                            }
                        });

                        if (firstInvalid) {
                            this.is_transfer_global = false; // Batalkan centang jika nama belum diisi
                            firstInvalid.reportValidity();
                            firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            return;
                        }

                        // Open modal to let user choose which items are TF
                        this.show_tf_modal = true;
                        // By default, mark all active money items as TF when global is turned ON
                        this.persons.forEach(p => {
                            Object.values(p.zakat).forEach(z => {
                                if (z.active && z.metode === 'uang') {
                                    z.is_transfer = true;
                                }
                            });
                        });
                    } else {
                        // Reset all to CASH if global is turned OFF
                        this.persons.forEach(p => {
                            Object.values(p.zakat).forEach(z => {
                                z.is_transfer = false;
                            });
                        });
                    }
                },

                init() {
                    // Initialize is_transfer_global from existing data (important for Edit mode)
                    if (this.persons && this.persons.length > 0) {
                        this.persons.forEach(p => {
                            if (p.zakat) {
                                Object.values(p.zakat).forEach(z => {
                                    if (z.is_transfer) this.is_transfer_global = true;
                                });
                            }
                        });
                    }

                    // Coba load old('items') array json jika ada validasi error
                    let oldItems = @json(old('items', []));
                    
                    if (oldItems && oldItems.length > 0) {
                        this.persons = [];
                        let personMap = {};
                        
                        oldItems.forEach((item, index) => {
                            let name = item.muzakki_name;
                            if (!personMap[name]) {
                                personMap[name] = {
                                    id: index + 1000,
                                    name: name,
                                    zakat: {
                                        fitrah: { active: false, metode: 'uang', nominal: '' },
                                        fidyah: { active: false, metode: 'uang', hari: '', nominal: '' },
                                        mal: { active: false, metode: 'uang', nominal: '' },
                                        infaq: { active: false, metode: 'uang', nominal: '' }
                                    }
                                };
                                this.persons.push(personMap[name]);
                            }
                            
                            let cat = item.category;
                            if (cat) {
                                let z = personMap[name].zakat[cat];
                                z.active = true;
                                z.metode = item.metode || 'uang';
                                z.id = item.id || null; // Simpan ID asli buat update
                                z.is_transfer = !!(item.is_transfer);
                                if (z.is_transfer) this.is_transfer_global = true;
                                if (cat === 'fidyah') z.hari = item.hari || '';
                                let rawNominal = item.metode === 'beras' ? (item.jumlah_beras_kg || '') : (item.nominal_uang || '');
                                z.nominal = item.metode === 'beras' ? this.formatBeras(String(rawNominal)) : this.formatCurrency(String(rawNominal));
                                if (cat === 'fitrah' || cat === 'fidyah') {
                                    z.is_custom = !!(item.is_custom);
                                }
                            }
                        });
                    }

                    // For edit mode, capture the initial state for change detection
                    this.$nextTick(() => {
                        this.initialSnapshot = this.getSnapshot();
                    });

                    // Fix for Browser Back Button (Reset Snapshot on page show)
                    window.onpageshow = (event) => {
                        this.submitting = false;
                        this.$nextTick(() => {
                            this.initialSnapshot = this.getSnapshot();
                        });
                    };
                },

                syncFirstName() {
                    if (this.persons.length > 0) {
                        // Jika nama muzakki pertama kosong ATAU masih sama dengan nama pembayar sebelumnya
                        // maka kita asumsikan sedang "Auto-Sync" dan ikut berubah.
                        let p0 = this.persons[0].name.trim();
                        if (p0 === '' || p0 === this.lastPembayarName) {
                            this.persons[0].name = this.pembayar_name;
                        }
                    }
                    this.lastPembayarName = this.pembayar_name;
                },

                addPerson() {
                    this.persons.push({
                        id: Date.now(),
                        name: '',
                        zakat: {
                            fitrah: { active: true, metode: 'uang', is_custom: false, is_transfer: false, nominal: '' },
                            fidyah: { active: false, metode: 'uang', is_custom: false, is_transfer: false, hari: '', nominal: '' },
                            mal: { active: false, metode: 'uang', is_transfer: false, nominal: '' },
                            infaq: { active: false, metode: 'uang', is_transfer: false, nominal: '' }
                        }
                    });
                },

                removePerson(index) {
                    if (this.persons.length > 1) {
                        this.persons.splice(index, 1);
                    }
                },

                // Generator Payload Hidden
                get txs() {
                    let list = [];
                    this.persons.forEach(person => {
                        // Use pembayar_name if individual name is missing for the first person
                        let name = person.name || (person.id === 1 ? this.pembayar_name : '');
                        if (!name) return;
                        
                        ['fitrah', 'fidyah', 'mal', 'infaq'].forEach(cat => {
                            let z = person.zakat[cat];
                            if (z.active) {
                                let isCustom = (cat === 'fitrah' || cat === 'fidyah') ? z.is_custom : true;
                                let val = this.parseNum(z.nominal, z.metode === 'beras');
                                
                                list.push({
                                    id: z.id || null,
                                    muzakki_name: name,
                                    category: cat,
                                    metode: z.metode,
                                    jiwa: 1,
                                    hari: cat === 'fidyah' ? z.hari : null,
                                    nominal_uang: z.metode !== 'beras' ? (isCustom ? val : null) : null,
                                    jumlah_beras_kg: z.metode === 'beras' ? (isCustom ? val : null) : null,
                                    is_custom: isCustom,
                                    is_transfer: z.is_transfer || false
                                });
                            }
                        });
                    });
                    return list;
                },

                prepareSubmit(e) {
                    // 1. Validasi minimal ada 1 jenis zakat aktif yang memuat nama
                    if (this.txs.length === 0) {
                        e.preventDefault();
                        alert('Peringatan: Pastikan Anda telah mengisi nama muzakki dan mencentang minimal 1 jenis zakat yang ditunaikan!');
                        return;
                    }

                    // 2. Validasi Regulasi Nominal Khusus (WAJIB > Standar)
                    let hasInvalidCustom = false;
                    this.persons.forEach(person => {
                        ['fitrah', 'fidyah'].forEach(cat => {
                            if (this.isBelowStandard(person, cat)) {
                                hasInvalidCustom = true;
                            }
                        });

                        // Cek juga Mal/Infaq jika aktif tapi 0
                        ['mal', 'infaq'].forEach(cat => {
                            if (person.zakat[cat].active && this.isBelowStandard(person, cat)) {
                                hasInvalidCustom = true;
                            }
                        });
                    });

                    if (hasInvalidCustom) {
                        e.preventDefault();
                        alert('Gagal Memproses: Terdapat input Nominal Khusus yang nilainya tidak melebihi standar atau masih kosong (ditandai merah). Jika ingin membayar sesuai standar, silakan hilangkan centang "Nominal Khusus".');
                        return;
                    }

                    // 3. Validasi Metode Transfer (Regulasi Baru)
                    if (this.is_transfer_global) {
                        let hasTfItem = this.txs.some(tx => tx.is_transfer);
                        if (!hasTfItem) {
                            e.preventDefault();
                            alert('Peringatan: Anda mengaktifkan Metode Transfer, namun belum memilih item mana yang ditransfer. Harap atur item transfer terlebih dahulu.');
                            this.openTfModal();
                            return;
                        }
                    }

                    this.submitting = true;
                }
            }
        }
    </script>
</x-app-layout>
