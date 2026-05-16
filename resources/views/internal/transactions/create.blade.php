<x-app-layout>
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

            @include('internal.transactions.partials.form-notice')

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
                        @include('internal.transactions.partials.payer-panel')
                        @include('internal.transactions.partials.transfer-panel')
                    </div>

                    <!-- Right Column (Matrix Members) -->
                    <div class="lg:col-span-3 space-y-4 max-w-full">
                        <div class="bg-white p-4 sm:p-5 rounded-2xl shadow-sm border border-gray-100">
                            <h3 class="font-bold text-xl text-gray-800">Detail Pembayaran</h3>
                            <p class="text-sm text-gray-500 mt-1">Satu baris mewakili satu jiwa/nama. Centang untuk memilih lebih dari 1 jenis zakat (Fitrah, Fidyah, Mal, dll.)</p>
                        </div>

                        <template x-for="(person, index) in persons" :key="person.id">
                            @include('internal.transactions.partials.person-card')
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
                        <div class="justify-end hidden">
                            <span class="text-sm font-bold text-gray-500 bg-gray-100 px-3 py-1 rounded-full border border-gray-200" x-text="'Total Transaksi Dipilih: '+txs.length"></span>
                        </div>

                        @include('internal.transactions.partials.form-actions')
                    </div>
                </div>
            </form>

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
        @json([
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
        ])
    </script>
</x-app-layout>
