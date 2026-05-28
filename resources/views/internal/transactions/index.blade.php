<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="space-y-1 text-center sm:text-left">
                <h2 class="ui-page-title">
                    <svg xmlns="http://www.w3.org/2000/svg" class="ui-page-title-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    Riwayat Transaksi
                </h2>
                <p class="text-sm text-slate-500">Pantau transaksi masuk, cari data yang dibutuhkan, lalu buka aksi operasional per transaksi.</p>
            </div>

            <div class="flex flex-col sm:flex-row items-center gap-2 w-full sm:w-auto">
                <a href="{{ route('internal.transactions.create') }}" class="ui-btn ui-btn-primary w-full px-4 py-3 sm:w-auto sm:py-2.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4" /></svg>
                    Input Baru
                </a>

                @if (auth()->check() && in_array(auth()->user()->role, ['admin', 'super_admin'], true))
                    <div class="flex items-center gap-1.5 w-full sm:w-auto">
                        <button type="button" x-data x-on:click="$dispatch('open-modal', 'export-daily-modal')" class="ui-btn ui-btn-secondary flex-1 px-3 py-2.5 text-xs text-emerald-700 hover:text-emerald-700 sm:flex-none sm:py-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                            Ekspor
                        </button>
                        <a href="{{ route('internal.transactions.trash') }}" class="ui-btn ui-btn-secondary flex-none px-3 py-2.5 text-gray-400 hover:text-red-600 sm:px-2.5 sm:py-2" title="Sampah">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-6 sm:py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if ($errors->any())
                <x-form-errors />
            @endif
            {{-- Transactions Table --}}
            <div class="ui-card overflow-hidden shadow-md">
                <div class="ui-toolbar-soft xl:flex-row xl:items-start">
                    <div class="max-w-full space-y-1 xl:max-w-[260px] xl:flex-none">
                        <div class="ui-section-title">
                            <div class="h-6 w-2 rounded-full bg-emerald-500"></div>
                            <h3 class="font-semibold text-gray-800">Daftar Transaksi</h3>
                        </div>
                        <p class="text-sm leading-6 text-slate-500">Filter, cari, lalu buka aksi cepat per transaksi dari tabel ini.</p>
                    </div>

                    @include('internal.transactions.partials.history-filters')
                </div>
                <div class="space-y-3 px-4 pb-4 pt-4 md:hidden">
                    @if (count($transactions) > 0)
                        @foreach ($transactions as $t)
                            <article class="ui-mobile-card">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <span class="inline-flex rounded-md bg-blue-50 px-2 py-1 font-mono text-[11px] font-semibold text-blue-600">{!! \App\Support\Format::highlight($t->no_transaksi, $q) !!}</span>
                                        <div class="mt-2 text-sm font-semibold leading-tight text-gray-800">{!! \App\Support\Format::highlight($t->pembayar_nama, $q) !!}</div>
                                        @if($t->muzakki_total > 1)
                                            <div class="mt-1 text-[11px] text-gray-500">+ {{ $t->muzakki_total - 1 }} muzakki lainnya</div>
                                        @endif
                                    </div>
                                    <div class="shrink-0 text-right">
                                        @if ($t->waktu_terima)
                                            <div class="text-[11px] font-semibold text-gray-500">{{ $t->waktu_terima->timezone('Asia/Jakarta')->format('d/m/Y') }}</div>
                                            <div class="mt-1 text-xs font-bold text-slate-700">{{ $t->waktu_terima->timezone('Asia/Jakarta')->format('H:i') }}</div>
                                        @else
                                            <div class="text-[11px] font-semibold text-gray-500">{{ $t->created_at->timezone('Asia/Jakarta')->format('d/m/Y') }}</div>
                                            <div class="mt-1 text-xs font-bold text-slate-700">{{ $t->created_at->timezone('Asia/Jakarta')->format('H:i') }}</div>
                                        @endif
                                    </div>
                                </div>

                                <div class="ui-mobile-meta-grid">
                                    <div class="ui-mobile-meta-item col-span-2">
                                        <p class="ui-mobile-meta-label">Kategori</p>
                                        <div class="mt-1">
                                            <x-zakat-category-tags :categories="$t->categories_list" />
                                        </div>
                                    </div>
                                    <div class="ui-mobile-meta-item">
                                        <p class="ui-mobile-meta-label">Bentuk</p>
                                        <div class="mt-1">
                                            <x-transaction-method-tags :methods="$t->methods_list" />
                                        </div>
                                    </div>
                                    <div class="ui-mobile-meta-item">
                                        <p class="ui-mobile-meta-label">Petugas</p>
                                        <div class="ui-mobile-meta-value">{{ $t->petugas?->name ?? '-' }}</div>
                                        <span class="mt-1 inline-flex items-center justify-center rounded px-2 py-0.5 text-[11px] font-bold uppercase bg-emerald-50 text-emerald-700 border border-emerald-100 whitespace-nowrap leading-tight text-center">
                                            {{ $t->shift_label }}
                                        </span>
                                    </div>
                                    <div class="ui-mobile-meta-item col-span-2">
                                        <p class="ui-mobile-meta-label">Nominal</p>
                                        <div class="mt-1 text-right">
                                            @if($t->total_uang > 0)
                                                <div class="flex items-center justify-end gap-1">
                                                    <span class="text-sm font-semibold text-gray-800">{{ $t->total_uang_display }}</span>
                                                    @if($t->has_transfer)
                                                        <x-transfer-badge />
                                                    @endif
                                                </div>
                                            @endif
                                            @if($t->total_beras > 0)
                                                <div class="mt-1 text-sm font-semibold text-gray-800">{{ $t->total_beras_display }}</div>
                                            @endif
                                        </div>
                                    </div>
                                    @if ($canViewRisk)
                                        <div class="ui-mobile-meta-item col-span-2">
                                            <div class="flex items-start justify-between gap-3">
                                                <div>
                                                    <p class="ui-mobile-meta-label">Risiko</p>
                                                    <p class="mt-1 text-xs leading-5 text-slate-500">Buka detail review untuk melihat alasan dan tindak lanjut.</p>
                                                </div>
                                                @if ($t->risk_level === \App\Models\TransactionRiskReview::LEVEL_WARNING || $t->risk_level === \App\Models\TransactionRiskReview::LEVEL_SUSPICIOUS)
                                                    <a href="{{ route('internal.anomalies.show', ['noTransaksi' => $t->no_transaksi]) }}" class="flex flex-col items-end gap-1">
                                                        <x-risk-level-badge :level="$t->risk_level" />
                                                        <x-review-status-badge :status="$t->review_status" />
                                                    </a>
                                                @else
                                                    <div class="flex flex-col items-end gap-1">
                                                        <x-risk-level-badge :level="$t->risk_level" />
                                                        <x-review-status-badge :status="$t->review_status" />
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                <div class="mt-4 grid grid-cols-2 gap-2">
                                    <a class="ui-btn ui-btn-secondary px-3 py-3 text-xs" href="{{ route('internal.transactions.show', ['transaction' => $t->id]) }}">
                                        Lihat
                                    </a>
                                    <a class="ui-btn ui-btn-accent px-3 py-3 text-xs" href="{{ route('internal.transactions.receipt', ['transaction' => $t->id]) }}" target="_blank" rel="noopener">
                                        Cetak
                                    </a>

                                    @can('update', $t)
                                        <a class="ui-btn ui-btn-secondary px-3 py-3 text-xs border-amber-200 text-amber-700 hover:bg-amber-50" href="{{ route('internal.transactions.edit', ['transaction' => $t->id]) }}">
                                            Ubah
                                        </a>
                                        <button type="button" x-data x-on:click="$dispatch('open-modal', 'trash-modal'); $dispatch('open-trash-modal', { id: {{ $t->id }}, no: '{{ $t->no_transaksi }}' })" class="ui-btn ui-btn-danger px-3 py-3 text-xs">
                                            Hapus
                                        </button>
                                    @else
                                        <button type="button" x-data x-on:click="$dispatch('open-modal', 'restricted-modal')" class="ui-btn ui-btn-secondary px-3 py-3 text-xs text-gray-400">
                                            Ubah
                                        </button>
                                        <button type="button" x-data x-on:click="$dispatch('open-modal', 'restricted-modal')" class="ui-btn ui-btn-secondary px-3 py-3 text-xs text-gray-400">
                                            Hapus
                                        </button>
                                    @endcan
                                </div>
                            </article>
                        @endforeach
                    @else
                        <div class="ui-empty-state">
                            <div class="flex flex-col items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-gray-200 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <span class="ui-empty-state-copy">
                                    {{ ($q || $year || $category) ? 'Data tidak ditemukan.' : 'Belum ada transaksi ditemukan.' }}
                                </span>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="hidden overflow-x-auto w-full md:block">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 text-left text-[11px] sm:text-xs font-bold text-gray-400 uppercase tracking-widest border-b border-gray-100">
                                <th class="px-2 sm:px-6 py-4">No. Transaksi</th>
                                <th class="px-2 sm:px-6 py-4">Waktu</th>
                                <th class="px-2 sm:px-6 py-4">Pembayar</th>
                                <th class="px-2 py-4 text-center">Kategori</th>
                                <th class="px-2 py-4 text-center">Bentuk</th>
                                <th class="px-2 sm:px-6 py-4 text-right">Total Nominal</th>
                                @if ($canViewRisk)
                                    <th class="px-2 sm:px-4 py-4 text-center">Risiko</th>
                                @endif
                                <th class="px-2 sm:px-6 py-4 text-center">Petugas</th>
                                <th class="px-3 py-4 text-center min-w-[120px]">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100/80">
                            @if (count($transactions) > 0)
                                @foreach ($transactions as $t)
                                <tr class="hover:bg-emerald-50/30 transition-colors">
                                    <td class="px-3 py-4 whitespace-nowrap">
                                        <span class="font-mono text-xs font-semibold text-blue-600 bg-blue-50 px-2 py-1 rounded-md">{!! \App\Support\Format::highlight($t->no_transaksi, $q) !!}</span>
                                    </td>
                                    <td class="px-3 py-4 text-gray-500 text-[13px] whitespace-nowrap">
                                        @if ($t->waktu_terima)
                                            <div class="leading-tight">
                                                <div>{{ $t->waktu_terima->timezone('Asia/Jakarta')->format('d/m/Y') }}</div>
                                                <div class="mt-1 text-[12px] text-slate-400">{{ $t->waktu_terima->timezone('Asia/Jakarta')->format('H:i') }}</div>
                                            </div>
                                        @else
                                            <div class="leading-tight">
                                                <div>{{ $t->created_at->timezone('Asia/Jakarta')->format('d/m/Y') }}</div>
                                                <div class="mt-1 text-[12px] text-slate-400">{{ $t->created_at->timezone('Asia/Jakarta')->format('H:i') }}</div>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-3 py-4">
                                        <div class="text-sm font-semibold text-gray-700 max-w-[180px] break-words whitespace-normal leading-tight">{!! \App\Support\Format::highlight($t->pembayar_nama, $q) !!}</div>
                                        @if($t->muzakki_total > 1)
                                            <div class="text-[11px] text-gray-400 mt-1 whitespace-nowrap">+ {{ $t->muzakki_total - 1 }} Muzakki Lainnya</div>
                                        @endif
                                    </td>
                                    <td class="px-2 py-4 text-center">
                                        <x-zakat-category-tags :categories="$t->categories_list" />
                                    </td>
                                    <td class="px-2 py-4">
                                        <x-transaction-method-tags :methods="$t->methods_list" class="mx-auto max-w-[90px]" />
                                    </td>
                                    <td class="px-3 py-4 text-right whitespace-nowrap">
                                        <div class="space-y-0.5">
                                            @if($t->total_uang > 0)
                                                <div class="font-semibold text-gray-800 text-sm flex items-center justify-end gap-1">
                                                    {{ $t->total_uang_display }}
                                                    @if($t->has_transfer)
                                                        <x-transfer-badge />
                                                    @endif
                                                </div>
                                            @endif
                                            @if($t->total_beras > 0)
                                                <div class="font-semibold text-gray-800 text-sm">{{ $t->total_beras_display }}</div>
                                            @endif
                                        </div>
                                    </td>
                                    @if ($canViewRisk)
                                        <td class="px-2 sm:px-4 py-4 text-center whitespace-nowrap">
                                            @if ($t->risk_level === \App\Models\TransactionRiskReview::LEVEL_WARNING || $t->risk_level === \App\Models\TransactionRiskReview::LEVEL_SUSPICIOUS)
                                                <a href="{{ route('internal.anomalies.show', ['noTransaksi' => $t->no_transaksi]) }}" class="flex flex-col items-center gap-1">
                                                    <x-risk-level-badge :level="$t->risk_level" />
                                                    <x-review-status-badge :status="$t->review_status" />
                                                </a>
                                            @else
                                                <div class="flex flex-col items-center gap-1">
                                                    <x-risk-level-badge :level="$t->risk_level" />
                                                    <x-review-status-badge :status="$t->review_status" />
                                                </div>
                                            @endif
                                        </td>
                                    @endif
                                    <td class="px-3 py-4 text-center text-gray-500 text-[13px] whitespace-nowrap">
                                        <div class="flex flex-col items-center gap-1 text-center">
                                            <span class="font-medium text-gray-700">{{ $t->petugas?->name ?? '-' }}</span>
                                            <span class="inline-flex items-center justify-center rounded px-2 py-0.5 text-[11px] font-bold uppercase bg-emerald-50 text-emerald-700 border border-emerald-100 whitespace-nowrap leading-tight text-center">
                                                {{ $t->shift_label }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-3 py-4 text-center whitespace-nowrap">
                                        <div class="flex items-center justify-center gap-1.5">
                                            <a class="ui-icon-button ui-icon-button-neutral px-2" href="{{ route('internal.transactions.show', ['transaction' => $t->id]) }}" title="Lihat" aria-label="Lihat transaksi">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                                <span class="ui-table-action-label">Lihat</span>
                                            </a>
                                            
                                            @can('update', $t)
                                                <a class="ui-icon-button ui-icon-button-amber px-2" href="{{ route('internal.transactions.edit', ['transaction' => $t->id]) }}" title="Ubah" aria-label="Ubah transaksi">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                                    <span class="ui-table-action-label">Ubah</span>
                                                </a>
                                            @else
                                                <button type="button" x-data x-on:click="$dispatch('open-modal', 'restricted-modal')" class="ui-icon-button ui-icon-button-disabled px-2" title="Ubah Terbatas" aria-label="Ubah terbatas">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                                    <span class="ui-table-action-label">Ubah</span>
                                                </button>
                                            @endcan

                                            <a class="ui-icon-button ui-icon-button-blue px-2" href="{{ route('internal.transactions.receipt', ['transaction' => $t->id]) }}" target="_blank" rel="noopener" title="Cetak Tanda Terima" aria-label="Cetak tanda terima">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                                                <span class="ui-table-action-label">Cetak</span>
                                            </a>

                                            @can('update', $t)
                                                <button type="button" x-data x-on:click="$dispatch('open-modal', 'trash-modal'); $dispatch('open-trash-modal', { id: {{ $t->id }}, no: '{{ $t->no_transaksi }}' })" class="ui-icon-button ui-icon-button-danger px-2" title="Hapus" aria-label="Hapus transaksi">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                    <span class="ui-table-action-label">Hapus</span>
                                                </button>
                                            @else
                                                <button type="button" x-data x-on:click="$dispatch('open-modal', 'restricted-modal')" class="ui-icon-button ui-icon-button-disabled px-2" title="Hapus Terbatas" aria-label="Hapus terbatas">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                    <span class="ui-table-action-label">Hapus</span>
                                                </button>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="{{ $canViewRisk ? 9 : 8 }}" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-gray-200 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            <span class="text-sm font-medium text-gray-400">
                                                {{ ($q || $year || $category) ? 'Data tidak ditemukan.' : 'Belum ada transaksi ditemukan.' }}
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                @if ($transactions->hasPages())
                    <div class="px-6 py-4 border-t border-gray-50">
                        {{ $transactions->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Modal Konfirmasi Pindah Sampah -->
    <x-modal name="trash-modal" :show="$errors->has('deleted_reason')" focusable>
        <form method="POST" x-data="{ id: '', no: '' }" x-on:open-trash-modal.window="id = $event.detail.id; no = $event.detail.no; $el.action = '{{ url('/internal/transactions') }}/' + id + '/trash';" class="p-6">
            @csrf
            <h2 class="text-lg font-medium text-gray-900">
                Yakin ingin memindahkan transaksi <span x-text="no" class="font-bold font-mono text-red-600"></span> ke Sampah?
            </h2>
            <p class="mt-1 text-sm text-gray-600">
                Transaksi akan otomatis dihapus permanen dari sistem setelah 30 hari.
            </p>
            <div class="mt-4">
                <x-input-label for="deleted_reason" :value="'Alasan Penghapusan'" />
                <textarea
                    id="deleted_reason"
                    name="deleted_reason"
                    rows="3"
                    class="ui-textarea mt-1 w-full focus:border-red-500 focus:ring-red-500"
                    required
                >{{ old('deleted_reason') }}</textarea>
                <x-input-error :messages="$errors->get('deleted_reason')" class="mt-2" />
            </div>
            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    Batal
                </x-secondary-button>
                <x-danger-button class="ml-3">
                    Ya, Pindahkan ke Sampah
                </x-danger-button>
            </div>
        </form>
    </x-modal>

    <!-- Modal Akses Terbatas untuk Staf -->
    <x-modal name="restricted-modal" focusable maxWidth="sm">
        <div class="p-6 text-center">
            <div class="w-16 h-16 bg-amber-50 rounded-full flex items-center justify-center mx-auto mb-4 border border-amber-100">
                <svg class="h-8 w-8 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
            </div>
            <h2 class="text-lg font-bold text-gray-900 mb-2">Akses Terbatas</h2>
            <p class="text-sm text-gray-600 mb-6 leading-relaxed">
                Maaf, sesuai regulasi, <strong>Staf</strong> hanya diperbolehkan mengubah atau menghapus transaksi <strong>milik sendiri</strong> yang dibuat pada <strong>hari yang sama</strong>.
                <br><br>
                Silakan hubungi Admin atau Super Admin untuk bantuan lebih lanjut.
            </p>
            <div class="flex justify-center">
                <button type="button" x-on:click="$dispatch('close')" class="ui-btn ui-btn-secondary px-6 py-2.5">
                    Mengerti
                </button>
            </div>
        </div>
    </x-modal>

    <!-- Modal Export Harian -->
    <x-modal name="export-daily-modal" focusable maxWidth="sm">
        <form method="GET" action="{{ route('internal.rekap.export.daily') }}" class="p-6">
            <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2 mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                Pilih Tanggal (Rekap Harian)
            </h2>
            <p class="mt-1 text-sm text-gray-600 mb-6">
                Silakan pilih tanggal transaksi yang ingin diekspor. Opsi yang tersedia hanya menyesuaikan dengan data transaksi tervalidasi di sistem.
            </p>

            <div class="mb-6">
                <x-input-label for="daily_date" :value="'Tanggal Transaksi'" class="mb-2" />
                @if(isset($availableDates) && count($availableDates) > 0)
                        <select id="daily_date" name="date" required class="ui-select w-full bg-white">
                            <option value="">-- Pilih Tanggal --</option>
                            @foreach ($availableDates as $rawDate => $formattedDate)
                                <option value="{{ $rawDate }}">{{ $formattedDate }}</option>
                            @endforeach
                        </select>
                @else
                    <div class="p-4 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-500 italic text-center">
                        Belum ada transaksi valid yang tersedia untuk diekspor.
                    </div>
                @endif
            </div>

            <div class="flex flex-col sm:flex-row justify-end items-stretch sm:items-center gap-3">
                <x-secondary-button x-on:click="$dispatch('close')" class="justify-center">Batal</x-secondary-button>
                @if(isset($availableDates) && count($availableDates) > 0)
                    <button type="submit" class="ui-btn ui-btn-primary" x-on:click="setTimeout(() => $dispatch('close'), 1500)">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                        Download Excel
                    </button>
                @endif
            </div>
        </form>
    </x-modal>

    <!-- Modal Export Tahunan -->
    <x-modal name="export-yearly-modal" focusable maxWidth="sm">
        <form method="GET" action="{{ route('internal.rekap.export.yearly') }}" class="p-6">
            <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2 mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                Pilih Tahun (Rekap Tahunan)
            </h2>
            <p class="mt-1 text-sm text-gray-600 mb-6">
                Silakan pilih tahun periode Zakat. Sistem akan mengakumulasi seluruh transaksi per hari pada tahun tersebut.
            </p>

            <div class="mb-6">
                <x-input-label for="yearly_year" :value="'Tahun Zakat'" class="mb-2" />
                @if(isset($availableYears) && count($availableYears) > 0)
                        <select id="yearly_year" name="year" required class="ui-select w-full bg-white focus:border-blue-500 focus:ring-blue-500">
                            <option value="">-- Pilih Tahun --</option>
                            @foreach ($availableYears as $optYear)
                                <option value="{{ $optYear }}">{{ $optYear }}</option>
                            @endforeach
                        </select>
                @else
                    <div class="p-4 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-500 italic text-center">
                        Belum ada transaksi valid yang tersedia untuk diekspor.
                    </div>
                @endif
            </div>

            <div class="flex flex-col sm:flex-row justify-end items-stretch sm:items-center gap-3">
                <x-secondary-button x-on:click="$dispatch('close')" class="justify-center">Batal</x-secondary-button>
                @if(isset($availableYears) && count($availableYears) > 0)
                    <button type="submit" class="ui-btn ui-btn-accent" x-on:click="setTimeout(() => $dispatch('close'), 1500)">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                        Download Excel
                    </button>
                @endif
            </div>
        </form>
    </x-modal>
</x-app-layout>
