<article class="ui-mobile-card stagger-item" x-data="{ open: false }">
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <span class="inline-flex rounded-md bg-slate-100 px-2 py-1 font-sans text-[11px] font-semibold text-slate-600">{!! \App\Support\Format::highlight($t->no_transaksi, $q) !!}</span>
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
                        @else
                            <x-cash-badge />
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
                <p class="ui-mobile-meta-label">Risiko</p>
                <div class="mt-1">
                    @if ($t->risk_level !== \App\Models\TransactionRiskReview::LEVEL_SAFE)
                        <button type="button" @click="open = !open" class="flex flex-col items-start gap-1 cursor-pointer hover:opacity-75 transition-opacity">
                            <x-risk-level-badge :level="$t->risk_level" />
                            <x-review-status-badge :status="$t->review_status" />
                        </button>
                    @else
                        <x-risk-level-badge :level="$t->risk_level" />
                    @endif
                </div>
            </div>
        @endif
    </div>

    @if ($canViewRisk && $t->risk_level !== \App\Models\TransactionRiskReview::LEVEL_SAFE)
    <div x-show="open" x-transition class="mt-3 pt-3 border-t border-amber-100 space-y-3">
        @if (!empty($t->risk_flags))
        <div class="flex flex-wrap gap-2">
            @foreach ($t->risk_flags as $flag)
                <span class="rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">
                    {{ \App\Models\TransactionRiskReview::flagLabel($flag) }}
                </span>
            @endforeach
        </div>
        @endif

        @if (!empty($t->risk_reasons))
        <div class="space-y-1">
            @foreach ($t->risk_reasons as $reason)
                <p class="text-xs text-slate-600">• {{ $reason }}</p>
            @endforeach
        </div>
        @endif

        <form method="POST" action="{{ route('internal.anomalies.review_status', $t->no_transaksi) }}" class="pt-2 flex flex-wrap items-center gap-2">
            @csrf @method('PATCH')
            <select name="review_status" class="ui-select text-xs py-1.5 flex-1">
                @foreach (\App\Models\TransactionRiskReview::REVIEW_STATUSES as $s)
                    <option value="{{ $s }}" @selected($t->review_status === $s)>
                        {{ \App\Models\TransactionRiskReview::reviewStatusLabel($s) }}
                    </option>
                @endforeach
            </select>
            <button type="submit" class="ui-btn ui-btn-primary py-1.5 text-xs flex-1">Simpan</button>
            <button type="button" @click="open = false" class="ui-btn ui-btn-secondary py-1.5 text-xs flex-1">Tutup</button>
        </form>
    </div>
    @endif

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
            <button type="button" @click="$dispatch('open-modal', 'trash-modal'); $dispatch('open-trash-modal', { id: {{ $t->id }}, no: '{{ $t->no_transaksi }}' })" class="ui-btn ui-btn-danger px-3 py-3 text-xs">
                Hapus
            </button>
        @else
            <button type="button" @click="$dispatch('open-modal', 'restricted-modal')" class="ui-btn ui-btn-secondary px-3 py-3 text-xs text-slate-400">
                Ubah
            </button>
            <button type="button" @click="$dispatch('open-modal', 'restricted-modal')" class="ui-btn ui-btn-secondary px-3 py-3 text-xs text-slate-400">
                Hapus
            </button>
        @endcan
    </div>
</article>
