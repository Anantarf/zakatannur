<article class="ui-mobile-card">
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <span class="inline-flex rounded-md bg-blue-50 px-2 py-1 font-sans text-[11px] font-semibold text-blue-600">{!! \App\Support\Format::highlight($t->no_transaksi, $q) !!}</span>
            <div class="mt-2 text-sm font-semibold leading-tight text-slate-800">{!! \App\Support\Format::highlight($t->pembayar_nama, $q) !!}</div>
            @if($t->muzakki_total > 1)
                <div class="mt-1 text-[11px] text-slate-500">+ {{ $t->muzakki_total - 1 }} muzakki lainnya</div>
            @endif
        </div>
        <div class="shrink-0 text-right">
            @if ($t->waktu_terima)
                <div class="text-[11px] font-semibold text-slate-500">{{ $t->waktu_terima->timezone('Asia/Jakarta')->format('d/m/Y') }}</div>
                <div class="mt-1 text-xs font-bold text-slate-700">{{ $t->waktu_terima->timezone('Asia/Jakarta')->format('H:i') }}</div>
            @else
                <div class="text-[11px] font-semibold text-slate-500">{{ $t->created_at->timezone('Asia/Jakarta')->format('d/m/Y') }}</div>
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
            <span class="mt-1 inline-flex items-center justify-center rounded px-2 py-0.5 text-[11px] font-bold uppercase bg-brand-50 text-brand-700 border border-brand-100 whitespace-nowrap leading-tight text-center">
                {{ $t->shift_label }}
            </span>
        </div>
        <div class="ui-mobile-meta-item col-span-2">
            <p class="ui-mobile-meta-label">Nominal</p>
            <div class="mt-1 text-right">
                @if($t->total_uang > 0)
                    <div class="flex items-center justify-end gap-1">
                        <span class="text-sm font-semibold text-slate-800">{{ $t->total_uang_display }}</span>
                        @if($t->has_transfer)
                            <x-transfer-badge />
                        @endif
                    </div>
                @endif
                @if($t->total_beras > 0)
                    <div class="mt-1 text-sm font-semibold text-slate-800">{{ $t->total_beras_display }}</div>
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
                    @if ($t->risk_level === \App\Models\TransactionRiskReview::LEVEL_WARNING)
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
            <button type="button" x-data x-on:click="$dispatch('open-modal', 'restricted-modal')" class="ui-btn ui-btn-secondary px-3 py-3 text-xs text-slate-400">
                Ubah
            </button>
            <button type="button" x-data x-on:click="$dispatch('open-modal', 'restricted-modal')" class="ui-btn ui-btn-secondary px-3 py-3 text-xs text-slate-400">
                Hapus
            </button>
        @endcan
    </div>
</article>
