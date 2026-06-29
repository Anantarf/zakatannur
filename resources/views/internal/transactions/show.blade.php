<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <h2 class="ui-page-title">
                <svg xmlns="http://www.w3.org/2000/svg" class="ui-page-title-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Ringkasan Transaksi
            </h2>
        </div>
    </x-slot>

    <div class="py-3 sm:py-5">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            
            {{-- session('status') is handled globally by the app layout toast --}}

            <div class="ui-card-strong overflow-hidden">
                <div>
                    <!-- Penyetor & Meta Header -->
                    <div class="border-b border-slate-200 px-4 py-3 sm:px-5">
                         <div class="mb-3 flex flex-col items-center justify-between gap-2 text-center sm:flex-row sm:items-start sm:text-left">
                            <div class="w-full sm:w-auto">
                                <p class="mb-1 text-[9px] font-semibold uppercase tracking-[0.14em] text-slate-400">Nomor Transaksi</p>
                                <h3 class="inline-block rounded border border-brand-100 bg-brand-50 px-2 py-0.5 font-sans text-sm font-bold tabular-nums text-brand-700 sm:text-base">{{ $noTransaksi }}</h3>
                            </div>
                            <div class="w-full sm:w-auto sm:text-right">
                                <p class="mb-1 text-[9px] font-semibold uppercase tracking-[0.14em] text-slate-400">Waktu Transaksi</p>
                                <p class="text-xs font-bold tabular-nums text-slate-600 sm:text-sm">{{ ($mainTx->waktu_terima ?? $mainTx->created_at)->format('d/m/Y H:i') }} WIB</p>
                            </div>
                        </div>
                        
                        <div class="text-center sm:text-left">
                            <p class="mb-0.5 text-[9px] font-semibold uppercase tracking-[0.14em] text-brand-600">Nama Pembayar</p>
                            <p class="text-base font-bold leading-tight text-slate-800 sm:text-lg">{{ $mainTx->pembayar_nama }}</p>
                        </div>
                    </div>

                    <!-- List Rincian Anggota -->
                    <div class="px-4 py-3 sm:px-5">
                        <div class="mb-3 flex items-center gap-2">
                            <span class="h-5 w-1 rounded-full bg-brand-500"></span>
                            <h4 class="text-sm font-bold uppercase tracking-[0.08em] text-slate-800">Rincian Pembayaran</h4>
                        </div>
                        <div class="space-y-3 md:hidden">
                            @php $rowNo = 1; @endphp
                            @foreach ($groupedArr as $muzakkiName => $txsArr)
                                @foreach ($txsArr as $tx)
                                    <article class="ui-mobile-card">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <div class="text-[10px] font-semibold uppercase tracking-[0.12em] text-slate-400">Muzakki {{ $rowNo++ }}</div>
                                                <p class="mt-1 text-sm font-bold leading-tight text-slate-900">{{ $muzakkiName }}</p>
                                            </div>
                                            <span class="inline-flex items-center rounded px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide {{ $tx->metode === 'beras' ? 'bg-amber-100 text-amber-700' : 'bg-brand-100 text-brand-700' }}">
                                                {{ $tx->metode_label }}
                                            </span>
                                        </div>

                                        <div class="ui-mobile-meta-grid">
                                            <div class="ui-mobile-meta-item col-span-2">
                                                <p class="ui-mobile-meta-label">Kategori</p>
                                                <div class="mt-1">
                                                    <x-zakat-category-tags :categories="[$tx->category]" />
                                                </div>
                                            </div>
                                            <div class="ui-mobile-meta-item">
                                                <p class="ui-mobile-meta-label">Keterangan</p>
                                                <div class="mt-1 text-right text-xs font-medium text-slate-500">
                                                    @if($tx->category === 'fitrah' && $tx->jiwa)
                                                        <span class="rounded-md border border-slate-200 bg-white px-2 py-1 font-bold text-slate-600">{{ $tx->jiwa }} Jiwa</span>
                                                    @elseif($tx->category === 'fidyah' && $tx->hari)
                                                        <span class="rounded-md border border-slate-200 bg-white px-2 py-1 font-bold text-slate-600">{{ $tx->hari }} Hari</span>
                                                    @else
                                                        -
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="ui-mobile-meta-item">
                                                <p class="ui-mobile-meta-label">Nominal</p>
                                                <div class="mt-1 text-right">
                                                    <p class="text-sm font-bold tabular-nums text-slate-900">
                                                        @if($tx->metode === 'beras')
                                                            {{ rtrim(rtrim(number_format($tx->jumlah_beras_kg, 2, ',', '.'), '0'), ',') }} <span class="ml-0.5 text-[10px] font-bold text-slate-400">kg</span>
                                                        @else
                                                            {{ \App\Support\Format::rupiah((int)$tx->nominal_uang) }}
                                                            @if($tx->is_transfer)
                                                                <x-transfer-badge class="ml-1" />
                                                            @endif
                                                        @endif
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </article>
                                @endforeach
                            @endforeach
                        </div>

                        <div class="hidden w-full overflow-x-auto rounded-lg border border-slate-200 bg-white md:block">
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="border-b border-slate-200 bg-slate-50 text-left text-[10px] font-semibold uppercase tracking-[0.1em] text-slate-500 sm:text-[11px]">
                                        <th class="px-4 py-2">Nama Muzakki</th>
                                        <th class="px-3 py-2 sm:px-4 text-center">Kategori</th>
                                        <th class="px-3 py-2 sm:px-4 text-center">Bentuk</th>
                                        <th class="px-3 py-2 text-right sm:px-4">Keterangan</th>
                                        <th class="px-4 py-2 text-right">Nominal</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @php $rowNo = 1; @endphp
                                    @foreach ($groupedArr as $muzakkiName => $txsArr)
                                        @php $txCount = count($txsArr); @endphp
                                        @foreach ($txsArr as $i => $tx)
                                            {{-- @var \App\Models\ZakatTransaction $tx --}}
                                            <tr class="transition-colors hover:bg-brand-50/30 {{ $i > 0 ? 'border-t border-dashed border-slate-200' : '' }}">
                                                {{-- Nama hanya muncul di baris pertama --}}
                                                @if($i === 0)
                                                    <td class="px-4 py-2.5 align-top" rowspan="{{ $txCount }}">
                                                        <div class="flex items-start gap-2.5">
                                                            <span class="mt-0.5 text-xs font-semibold text-slate-400">{{ $rowNo++ }}.</span>
                                                            <p class="text-[13px] font-bold leading-tight text-slate-800">{{ $muzakkiName }}</p>
                                                        </div>
                                                    </td>
                                                @endif
                                                <td class="px-3 py-2.5 sm:px-4 text-center">
                                                    <x-zakat-category-tags :categories="[$tx->category]" />
                                                </td>
                                                <td class="px-3 py-2.5 sm:px-4 text-center">
                                                    <span class="inline-flex items-center rounded px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wider {{ $tx->metode === 'beras' ? 'bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-600/20' : 'bg-brand-50 text-brand-700 ring-1 ring-inset ring-brand-600/20' }}">
                                                        {{ $tx->metode_label }}
                                                    </span>
                                                </td>
                                                <td class="px-3 py-2.5 text-right text-xs font-medium text-slate-500 sm:px-4">
                                                    @if($tx->category === 'fitrah' && $tx->jiwa)
                                                        <span class="rounded border border-slate-200 bg-slate-50 px-1.5 py-0.5 text-[10px] font-bold text-slate-600">{{ $tx->jiwa }} Jiwa</span>
                                                    @elseif($tx->category === 'fidyah' && $tx->hari)
                                                        <span class="rounded border border-slate-200 bg-slate-50 px-1.5 py-0.5 text-[10px] font-bold text-slate-600">{{ $tx->hari }} Hari</span>
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                                <td class="px-4 py-2.5 text-right">
                                                    <p class="whitespace-nowrap text-[13px] font-bold tabular-nums text-slate-800">
                                                        @if($tx->metode === 'beras')
                                                            {{ rtrim(rtrim(number_format($tx->jumlah_beras_kg, 2, ',', '.'), '0'), ',') }} <span class="ml-0.5 text-[10px] font-semibold text-slate-400">kg</span>
                                                        @else
                                                            {{ \App\Support\Format::rupiah((int)$tx->nominal_uang) }}
                                                            @if($tx->is_transfer)
                                                                <x-transfer-badge class="ml-1" />
                                                            @endif
                                                        @endif
                                                    </p>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Info Petugas & Shift -->
                        <div class="ui-panel-note mt-2.5">
                            <span class="mb-1 block text-[9px] font-semibold uppercase tracking-[0.14em] text-slate-400">Penerima/Shift</span>
                            <div class="mt-1 flex items-center gap-2">
                                <div class="flex h-5 w-5 items-center justify-center rounded-full border border-brand-200 bg-brand-100 text-brand-600">
                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>
                                <span class="text-xs font-semibold text-slate-800">
                                    {{ $mainTx->petugas ? $mainTx->petugas->name : 'Sistem' }} / {{ $shiftLabel }}
                                </span>
                            </div>
                        </div>

                        <!-- Ringkasan Total Akhir -->
                        <div class="mt-3 border-t border-slate-200 pt-3">
                            <div class="rounded-lg border border-brand-100 bg-brand-50/50 px-4 py-3 sm:px-4">
                                @if($totalUang > 0)
                                    <div class="flex w-full items-center justify-between gap-4 sm:justify-end">
                                        <span class="text-[9px] font-semibold uppercase tracking-[0.14em] text-slate-500">Total Uang</span>
                                        <div class="flex flex-col items-center sm:items-end">
                                            <p class="rounded border border-brand-100 bg-white px-2 py-0.5 text-sm font-bold tabular-nums text-slate-900 sm:text-base">
                                                {{ \App\Support\Format::rupiah((int)$totalUang) }}
                                            </p>
                                            @if($totalTf > 0)
                                                <div class="mt-1 flex items-center gap-1.5 text-[9px] font-bold uppercase tracking-tight text-slate-500">
                                                    <span>Cash: <span class="text-slate-800">Rp {{ number_format($totalCash, 0, ',', '.') }}</span></span>
                                                    <span class="font-normal text-brand-300">/</span>
                                                    <span class="text-brand-600">TF: <span class="font-bold">Rp {{ number_format($totalTf, 0, ',', '.') }}</span></span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                                @if($totalBeras > 0)
                                    <div class="flex w-full items-center justify-between gap-4 sm:justify-end {{ $totalUang > 0 ? 'mt-2 border-t border-brand-100/70 pt-2' : '' }}">
                                        <span class="text-[9px] font-semibold uppercase tracking-[0.14em] text-slate-500">Total Beras</span>
                                        <p class="rounded border border-amber-100 bg-white px-2 py-0.5 text-sm font-bold tabular-nums text-amber-700 sm:text-base">
                                            {{ \App\Support\Format::kg((float)$totalBeras) }}
                                        </p>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Footer Action Buttons -->
                        <div class="mt-4 grid gap-2 border-t border-slate-200 px-2 pt-3 sm:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto] sm:px-0">
                             <a href="{{ route('internal.transactions.receipt', ['transaction' => $mainTx->id]) }}" target="_blank" class="ui-btn ui-btn-primary px-5 py-3 text-sm font-bold">
                                 <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                                 Cetak Tanda Terima
                             </a>
                             <a href="{{ route('internal.transactions.create') }}" class="ui-btn ui-btn-primary px-5 py-3 text-sm font-bold">
                                 <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" /></svg>
                                 Input Baru
                             </a>
                             @can('update', $mainTx)
                                 <a href="{{ route('internal.transactions.edit', ['transaction' => $mainTx->id]) }}" class="ui-btn ui-btn-secondary px-5 py-3 text-xs font-bold uppercase tracking-[0.12em]">
                                     <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h14a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                     Ubah
                                 </a>
                             @endcan
                        </div>
                    </div>
                </div>
            </div>
            
            <p class="ui-label mt-4 text-center text-slate-400/80">Dokumentasi Administrasi Masjid An-Nur</p>
        </div>
    </div>
</x-app-layout>
