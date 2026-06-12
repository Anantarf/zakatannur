<tr class="hover:bg-emerald-50/30 transition-colors">
    <td class="px-3 py-4 whitespace-nowrap">
        <span class="font-sans text-xs font-semibold text-blue-600 bg-blue-50 px-2 py-1 rounded-md">{!! \App\Support\Format::highlight($t->no_transaksi, $q) !!}</span>
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
            @if ($t->risk_level === \App\Models\TransactionRiskReview::LEVEL_WARNING)
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
            <a class="ui-icon-button ui-icon-button-slate px-2" href="{{ route('internal.transactions.show', ['transaction' => $t->id]) }}" title="Lihat" aria-label="Lihat transaksi">
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