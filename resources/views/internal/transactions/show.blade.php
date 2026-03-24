<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <h2 class="font-bold text-xl sm:text-2xl text-emerald-800 leading-tight flex items-center justify-center sm:justify-start gap-2 text-center sm:text-left">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Ringkasan Transaksi
            </h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            
             @if (session('status'))
                <div class="mb-6 px-5 py-4 rounded-2xl bg-emerald-600 text-white text-sm font-bold flex items-center gap-3 shadow-[0_10px_30px_rgba(16,185,129,0.3)] animate-bounce-subtle mx-2 sm:mx-0">
                    <div class="w-8 h-8 rounded-full bg-white flex items-center justify-center shrink-0">
                        <svg class="h-5 w-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                    </div>
                    <span>{{ session('status') }}</span>
                </div>
                <style>
                    @keyframes bounce-subtle {
                        0%, 100% { transform: translateY(0); }
                        50% { transform: translateY(-3px); }
                    }
                    .animate-bounce-subtle { animation: bounce-subtle 3s ease-in-out infinite; }
                </style>
            @endif

            <div class="bg-white rounded-2xl shadow-lg shadow-gray-200/50 border border-emerald-100/30 overflow-hidden relative">
                <!-- Subtle Emerald Accent -->
                <div class="absolute inset-0 bg-gradient-to-br from-emerald-50/20 via-white to-white pointer-events-none"></div>
                
                <div class="relative z-10">
                    <!-- Penyetor & Meta Header -->
                    <div class="px-6 py-5 sm:px-8 sm:py-6 border-b border-gray-100/80">
                         <div class="flex flex-col sm:flex-row justify-between items-center sm:items-start gap-3 sm:gap-4 mb-6 text-center sm:text-left">
                            <div class="w-full sm:w-auto">
                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-1">Nomor Transaksi</p>
                                <h3 class="text-base sm:text-lg font-bold text-emerald-700 tabular-nums bg-emerald-50 px-3 py-1 rounded-lg border border-emerald-100 inline-block">{{ $noTransaksi }}</h3>
                            </div>
                            <div class="w-full sm:w-auto sm:text-right">
                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-1">Waktu Transaksi</p>
                                <p class="text-sm font-bold text-gray-600 tabular-nums">{{ $mainTx->created_at->format('d/m/Y H:i') }} WIB</p>
                            </div>
                        </div>
                        
                        <div class="text-center sm:text-left">
                            <p class="text-[10px] font-black text-emerald-600 uppercase tracking-[0.2em] mb-1">Nama Pembayar (Muzakki)</p>
                            <p class="text-lg sm:text-xl font-black text-slate-800 tracking-tight leading-tight">{{ $mainTx->pembayar_nama }}</p>
                        </div>
                    </div>

                    <!-- List Rincian Anggota -->
                    <div class="px-6 py-5 sm:px-8 sm:py-6">
                        <div class="flex items-center gap-2 mb-4">
                            <span class="w-1 h-5 bg-emerald-500 rounded-full"></span>
                            <h4 class="text-sm font-bold text-gray-800 uppercase tracking-wide">Rincian Pembayaran</h4>
                        </div>
                        <div class="overflow-x-auto w-full border border-gray-100 rounded-xl bg-white shadow-sm">
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="bg-gray-50 border-b border-gray-100 text-left text-[11px] sm:text-xs font-semibold text-gray-400 uppercase tracking-wider">
                                        <th class="px-6 py-4">Nama Muzakki</th>
                                        <th class="px-3 sm:px-6 py-4">Kategori</th>
                                        <th class="px-3 sm:px-6 py-4">Bentuk</th>
                                        <th class="px-3 sm:px-6 py-4 text-right">Keterangan</th>
                                        <th class="px-3 sm:px-6 py-4 text-right">Nominal</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50">
                                    @php
                                        $rowNo = 1;
                                    @endphp
                                    <?php foreach ($groupedArr as $muzakkiName => $txsArr): ?>
                                        <?php $txCount = count($txsArr); ?>
                                        <?php foreach ($txsArr as $i => $tx): ?>
                                            <?php /** @var \App\Models\ZakatTransaction $tx */ ?>
                                            <tr class="hover:bg-emerald-50/30 transition-colors <?php echo $i > 0 ? 'border-t border-dashed border-gray-100' : ''; ?>">
                                                {{-- Nama hanya muncul di baris pertama --}}
                                                @if($i === 0)
                                                    <td class="px-3 sm:px-6 py-4 align-top" rowspan="{{ $txCount }}">
                                                        <div class="flex items-start gap-3">
                                                            <span class="text-xs font-semibold text-gray-400 mt-0.5 min-w-[1.25rem]">{{ $rowNo++ }}.</span>
                                                            <p class="text-sm font-bold text-gray-900 leading-tight">{{ $muzakkiName }}</p>
                                                        </div>
                                                    </td>
                                                @endif
                                                <td class="px-3 sm:px-6 py-4">
                                                    <x-zakat-category-tags :categories="[$tx->category]" />
                                                </td>
                                                <td class="px-3 sm:px-6 py-4">
                                                    <span class="inline-flex items-center px-2.5 py-1 rounded text-[11px] font-bold uppercase tracking-wider {{ $tx->metode == 'beras' ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700' }}">
                                                        {{ \App\Models\ZakatTransaction::getMethodLabel($tx->metode) }}
                                                    </span>
                                                </td>
                                                <td class="px-3 sm:px-6 py-4 text-right font-medium text-gray-500 text-xs">
                                                    @if($tx->category == 'fitrah' && $tx->jiwa)
                                                        <span class="px-2 py-1 bg-gray-50 rounded-md border border-gray-100 font-bold text-gray-600">{{ $tx->jiwa }} Jiwa</span>
                                                    @elseif($tx->category == 'fidyah' && $tx->hari)
                                                        <span class="px-2 py-1 bg-gray-50 rounded-md border border-gray-100 font-bold text-gray-600">{{ $tx->hari }} Hari</span>
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                                <td class="px-2 sm:px-6 py-4 text-right">
                                                    <p class="text-[10px] sm:text-sm font-bold text-gray-900 tabular-nums whitespace-nowrap">
                                                        @if($tx->metode == 'beras')
                                                            {{ rtrim(rtrim(number_format($tx->jumlah_beras_kg, 2, ',', '.'), '0'), ',') }} <span class="text-[9px] sm:text-xs font-bold text-gray-400 ml-0.5">kg</span>
                                                        @else
                                                            <span class="text-[9px] sm:text-xs font-bold text-gray-400 mr-0.5">Rp</span>{{ number_format($tx->nominal_uang, 0, ',', '.') }}
                                                            @if($tx->is_transfer)
                                                                <span class="ml-1 text-[9px] font-black text-emerald-600 bg-emerald-50 px-1 py-0.5 rounded border border-emerald-100 italic">TF</span>
                                                            @endif
                                                        @endif
                                                    </p>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Info Petugas & Shift -->
                        <div class="px-5 py-4 border-t border-gray-100 bg-gray-50/50 mt-2 rounded-xl">
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-1">
                                Penerima/Shift
                            </span>
                            <div class="flex items-center gap-2 mt-1">
                                <div class="h-6 w-6 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center border border-emerald-200 shadow-sm">
                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>
                                <span class="text-sm font-semibold text-gray-800">
                                    {{ $mainTx->petugas ? $mainTx->petugas->name : 'Sistem' }} / {{ $shiftLabel }}
                                </span>
                            </div>
                        </div>

                        <!-- Ringkasan Total Akhir -->
                        <div class="mt-6 pt-5 border-t-2 border-gray-100">
                            <div class="flex flex-col items-center sm:items-end space-y-3 px-6 py-5 bg-emerald-50/50 rounded-2xl border border-emerald-100 bg-gradient-to-br from-emerald-50 to-white relative overflow-hidden shadow-inner">
                                <div class="absolute top-0 right-0 -translate-y-1/2 translate-x-1/4 w-40 h-40 bg-emerald-500/5 rounded-full blur-3xl"></div>
                                
                                @if($totalUang > 0)
                                    <div class="flex items-center gap-4 relative z-10 w-full justify-between sm:justify-end">
                                        <span class="text-[10px] sm:text-[11px] font-black text-gray-400 uppercase tracking-[0.2em]">Total Uang</span>
                                        <div class="flex flex-col items-center sm:items-end">
                                            <p class="text-base sm:text-xl font-black text-slate-900 tabular-nums bg-white px-3 py-1 rounded-lg border border-emerald-100 shadow-sm">
                                                <span class="text-xs font-bold text-gray-400 mr-1">Rp</span>{{ number_format($totalUang, 0, ',', '.') }}
                                            </p>
                                            @if($totalTf > 0)
                                                <div class="flex items-center gap-2 mt-1.5 text-[10px] sm:text-[11px] font-bold text-gray-500 uppercase tracking-tight">
                                                    <span>Cash: <span class="text-gray-800">Rp {{ number_format($totalCash, 0, ',', '.') }}</span></span>
                                                    <span class="text-emerald-300 font-normal">/</span>
                                                    <span class="text-emerald-600">TF: <span class="font-black">Rp {{ number_format($totalTf, 0, ',', '.') }}</span></span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                                @if($totalBeras > 0)
                                    <div class="flex items-center gap-4 relative z-10 w-full justify-between sm:justify-end {{ $totalUang > 0 ? 'pt-3 mt-1 border-t border-emerald-100/30' : '' }}">
                                        <span class="text-[10px] sm:text-[11px] font-black text-gray-400 uppercase tracking-[0.2em]">Total Beras</span>
                                        <p class="text-base sm:text-xl font-black text-amber-700 tabular-nums bg-white px-3 py-1 rounded-lg border border-amber-100 shadow-sm">
                                            {{ rtrim(rtrim(number_format($totalBeras, 2, ',', '.'), '0'), ',') }} <span class="text-xs font-bold text-gray-400 ml-1 uppercase">kg</span>
                                        </p>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Footer Action Buttons -->
                        <div class="mt-8 pt-6 border-t border-gray-100 flex flex-col sm:flex-row gap-3 px-2 sm:px-0">
                             <a href="{{ route('internal.transactions.receipt', ['transaction' => $mainTx->id]) }}" target="_blank" class="flex-1 flex justify-center items-center gap-2 px-6 py-4 bg-emerald-600 hover:bg-emerald-700 text-white text-base font-black rounded-xl shadow-lg shadow-emerald-200 transition-all transform hover:-translate-y-0.5 active:scale-95 sm:order-1">
                                 <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                                 CETAK TANDA TERIMA
                             </a>
                             <a href="{{ route('internal.transactions.create') }}" class="flex-1 flex justify-center items-center gap-2 px-6 py-4 bg-blue-600 hover:bg-blue-700 text-white text-base font-black rounded-xl shadow-lg shadow-blue-200 transition-all transform hover:-translate-y-0.5 active:scale-95 sm:order-2">
                                 <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" /></svg>
                                 INPUT BARU
                             </a>
                             @php
                                 $canModify = auth()->user()->role !== 'staff' || 
                                             ($mainTx->petugas_id === auth()->id() && $mainTx->created_at->isToday());
                             @endphp
                             
                             @if($canModify)
                                 <a href="{{ route('internal.transactions.edit', ['transaction' => $mainTx->id]) }}" class="flex-none px-6 py-4 bg-white border border-gray-200 hover:bg-gray-50 text-gray-500 hover:text-gray-700 text-xs font-black rounded-xl transition-all shadow-sm flex items-center justify-center sm:order-3 uppercase tracking-widest">
                                     <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h14a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                     EDIT
                                 </a>
                             @endif
                        </div>
                    </div>
                </div>
            </div>
            
            <p class="mt-6 text-center text-xs text-gray-400 font-semibold uppercase tracking-[0.3em] opacity-60">Dokumentasi Administrasi Masjid An-Nur</p>
        </div>
    </div>
</x-app-layout>
