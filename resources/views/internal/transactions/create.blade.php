<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="space-y-1 text-center sm:text-left">
                <h2 class="text-xl font-bold leading-tight text-brand-900 sm:text-2xl">
                    {{ isset($isEdit) ? 'Ubah Transaksi ' . $mainTx->no_transaksi : 'Input Transaksi' }}
                </h2>
                <p class="text-sm text-slate-500">
                    {{ isset($isEdit) ? 'Perbarui data transaksi, lalu simpan.' : 'Isi pembayar dan pilih kategori zakat per jiwa.' }}
                </p>
            </div>
            <div class="mx-auto inline-flex w-fit items-center justify-center rounded-full border border-brand-100 bg-brand-50 px-3 py-1.5 text-[10px] font-bold uppercase tracking-[0.14em] text-brand-700 sm:mx-0">
                Periode {{ $activeYear }}
            </div>
        </div>
    </x-slot>

    <div class="py-4 sm:py-6" x-data="zakatForm()">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            
            @if ($errors->any())
                <div class="mb-4 rounded-xl border border-red-200 bg-red-50 p-4 text-red-900 shadow-sm">
                    <div class="font-bold text-red-800 mb-3 border-b border-red-200 pb-2">Terdapat Kesalahan Input:</div>
                    <ul class="list-disc pl-5 text-sm space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @include('internal.transactions.partials.form-notice')

            <form method="POST" action="{{ isset($isEdit) ? route('internal.transactions.update', ['transaction' => $mainTx->id]) : route('internal.transactions.store') }}" @submit="prepareSubmit" class="space-y-4">
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

                <div class="grid grid-cols-1 gap-4 lg:grid-cols-4">
                    
                    <!-- Left Sidebar (Global Payer) -->
                    <div class="space-y-4 lg:col-span-1">
                        @include('internal.transactions.partials.payer-panel')
                        @include('internal.transactions.partials.transfer-panel')
                    </div>

                    <!-- Right Column (Matrix Members) -->
                    <div class="lg:col-span-3 space-y-4 max-w-full">
                        <div class="ui-card-strong p-4 sm:p-5">
                            <h3 class="text-lg font-bold text-slate-800">Detail Pembayaran</h3>
                            <p class="mt-1 text-sm text-slate-500">Aktifkan jenis zakat, lalu isi nominal atau beratnya.</p>
                        </div>

                        <template x-for="(person, index) in persons" :key="person.id">
                            @include('internal.transactions.partials.person-card')
                        </template>
                        
                        <!-- Button Tambah Orang di Bawah Box Terakhir -->
                        <div class="pb-4">
                            <button type="button" @click="addPerson()" class="group flex w-full items-center justify-center gap-2 rounded-card border-2 border-dashed border-brand-200 bg-brand-50 px-4 py-3 font-bold text-brand-700 transition-all hover:border-brand-300 hover:bg-brand-100">
                                <div class="rounded-full bg-brand-500 p-1 text-white transition-transform group-hover:scale-110">
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
                        
                        @include('internal.transactions.partials.form-actions')
                    </div>
                </div>
            </form>

            <!-- Unsaved Changes Modal (Light Confirmation) -->
            <template x-if="showUnsavedModal">
                <div class="fixed inset-0 z-50 flex items-center justify-center" x-transition>
                    <div class="absolute inset-0 bg-slate-900/40" @click="showUnsavedModal = false" x-transition></div>
                    <div class="relative bg-white rounded-xl shadow-lg max-w-sm mx-4 p-5 z-10" x-transition>
                        <h3 class="text-base font-semibold text-slate-900 mb-1">Perubahan belum disimpan</h3>
                        <p class="text-sm text-slate-500 mb-5">Anda akan keluar dari halaman ini.</p>
                        <div class="flex gap-2 justify-end">
                            <button type="button" @click="showUnsavedModal = false; pendingNavigation = null" class="px-4 py-2 text-sm font-semibold text-slate-700 rounded-lg hover:bg-slate-100 transition">
                                Batal
                            </button>
                            <button type="button" @click="discardChanges()" class="px-4 py-2 text-sm font-semibold text-white bg-brand-600 rounded-lg hover:bg-brand-700 transition">
                                Keluar
                            </button>
                        </div>
                    </div>
                </div>
            </template>

    @include('internal.transactions.partials.transfer-modal')
</div>
</div>

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
    <script id="transaction-form-config" type="application/json">
        {!! json_encode([
            'isEdit' => isset($isEdit),
            'pembayarName' => old('pembayar_nama', $mainTx->pembayar_nama ?? ''),
            'pembayarAddress' => old('pembayar_alamat', $mainTx->pembayar_alamat ?? ''),
            'pembayarPhone' => old('pembayar_phone', $mainTx->pembayar_phone ?? ''),
            'shift' => old('shift', $mainTx->shift ?? ''),
            'fitrahBase' => $fitrahUang,
            'fidyahBase' => $fidyahUang,
            'fidyahBeras' => $fidyahBeras,
            'berasPerJiwa' => $berasPerJiwa,
            'autocompleteUrl' => route('internal.muzakki.autocomplete'),
            'initialPersons' => $initialPersons,
            'oldItems' => old('items', []),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>
</x-app-layout>
