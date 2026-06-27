<tr class="group transition-colors hover:bg-brand-50/30 stagger-item">
    <td class="whitespace-nowrap px-3 py-3 sm:px-5">
        <span class="font-sans text-xs font-semibold text-slate-600 transition-colors group-hover:text-brand-700 bg-slate-100 group-hover:bg-brand-100/50 px-2 py-1 rounded-md">{!! \App\Support\Format::highlight($t->no_transaksi, $q) !!}</span>
    </td>
    <td class="whitespace-nowrap px-3 py-3 sm:px-5">
        @if ($t->waktu_terima)
            <div class="leading-tight">
                <div class="font-bold text-sm text-slate-800">{{ $t->waktu_terima->timezone('Asia/Jakarta')->format('d/m/Y') }}</div>
                <div class="mt-0.5 text-xs font-medium text-slate-600">{{ $t->waktu_terima->timezone('Asia/Jakarta')->format('H:i') }}</div>
            </div>
        @else
            <div class="leading-tight">
                <div class="font-bold text-sm text-slate-800">{{ $t->created_at->timezone('Asia/Jakarta')->format('d/m/Y') }}</div>
                <div class="mt-0.5 text-xs font-medium text-slate-600">{{ $t->created_at->timezone('Asia/Jakarta')->format('H:i') }}</div>
            </div>
        @endif
    </td>
    <td class="px-3 py-3 sm:px-5 flex-1">
        <div class="max-w-xs whitespace-normal break-words text-sm font-bold leading-tight text-slate-800">{!! \App\Support\Format::highlight($t->pembayar_nama, $q) !!}</div>
        @if($t->muzakki_total > 1)
            <div class="mt-1 whitespace-nowrap text-xs text-slate-600 font-medium">+ {{ $t->muzakki_total - 1 }} Muzakki Lainnya</div>
        @endif
        @if($t->categories_list || $t->methods_list)
            <div class="mt-2 flex flex-wrap gap-1">
                <x-zakat-category-tags :categories="$t->categories_list" />
                <x-transaction-method-tags :methods="$t->methods_list" />
            </div>
        @endif
    </td>
    <td class="whitespace-nowrap px-3 py-3 text-right sm:px-5">
        <div class="space-y-0.5">
            @if($t->total_uang > 0)
                <div class="flex items-center justify-end gap-1 text-sm font-semibold text-slate-800">
                    {{ $t->total_uang_display }}
                    @if($t->has_transfer)
                        <x-transfer-badge />
                    @else
                        <x-cash-badge />
                    @endif
                </div>
            @endif
            @if($t->total_beras > 0)
                <div class="text-sm font-semibold text-slate-800">{{ $t->total_beras_display }}</div>
            @endif
        </div>
    </td>
    @if ($canViewRisk)
        <td class="whitespace-nowrap px-2 py-3 text-center sm:px-4">
            @if ($t->risk_level !== \App\Models\TransactionRiskReview::LEVEL_SAFE)
                <button type="button" @click="open = !open" class="flex flex-col items-center gap-1 cursor-pointer hover:opacity-75 transition-opacity">
                    <x-risk-level-badge :level="$t->risk_level" />
                    <x-review-status-badge :status="$t->review_status" />
                </button>
            @else
                <x-risk-level-badge :level="$t->risk_level" />
            @endif
        </td>
    @endif
    <td class="whitespace-nowrap px-3 py-3 text-center sm:px-5">
        <div class="flex flex-col items-center gap-1">
            <span class="font-medium text-slate-700 text-sm">{{ $t->petugas?->name ?? '-' }}</span>
            <span class="inline-flex items-center justify-center rounded px-2 py-0.5 text-[11px] font-bold uppercase bg-brand-50 text-brand-700 border border-brand-100 whitespace-nowrap leading-tight">
                {{ $t->shift_label }}
            </span>
        </div>
    </td>
    <td class="whitespace-nowrap px-3 py-3 text-center sm:px-5">
        <div class="flex items-center justify-center gap-1">
            <a class="ui-btn ui-btn-secondary px-3 py-1.5 text-xs" href="{{ route('internal.transactions.show', ['transaction' => $t->id]) }}" title="Lihat">
                Lihat
            </a>
            <a class="ui-btn ui-btn-secondary px-3 py-1.5 text-xs" href="{{ route('internal.transactions.receipt', ['transaction' => $t->id]) }}" target="_blank" rel="noopener" title="Cetak">
                Cetak
            </a>
            <div class="relative group">
                <button type="button" class="ui-btn ui-btn-secondary px-2 py-1.5 text-xs" title="Opsi lainnya">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 8c1.1 0 2-0.9 2-2s-0.9-2-2-2-2 0.9-2 2 0.9 2 2 2zm0 2c-1.1 0-2 0.9-2 2s0.9 2 2 2 2-0.9 2-2-0.9-2-2-2zm0 6c-1.1 0-2 0.9-2 2s0.9 2 2 2 2-0.9 2-2-0.9-2-2-2z"/>
                    </svg>
                </button>
                <div class="absolute right-0 mt-1 hidden group-hover:block bg-white border border-slate-200 rounded-lg shadow-lg z-10 min-w-[140px]">
                    @can('update', $t)
                        <a href="{{ route('internal.transactions.edit', ['transaction' => $t->id]) }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 first:rounded-t-lg">
                            Ubah
                        </a>
                        <button type="button" @click="$dispatch('open-modal', 'trash-modal'); $dispatch('open-trash-modal', { id: {{ $t->id }}, no: '{{ $t->no_transaksi }}' })" class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 last:rounded-b-lg border-t border-slate-200">
                            Hapus
                        </button>
                    @else
                        <button type="button" @click="$dispatch('open-modal', 'restricted-modal')" class="block w-full text-left px-4 py-2 text-sm text-slate-400 hover:bg-slate-50 first:rounded-t-lg">
                            Ubah (Terbatas)
                        </button>
                        <button type="button" @click="$dispatch('open-modal', 'restricted-modal')" class="block w-full text-left px-4 py-2 text-sm text-slate-400 hover:bg-red-50 last:rounded-b-lg border-t border-slate-200">
                            Hapus (Terbatas)
                        </button>
                    @endcan
                </div>
            </div>
        </div>
    </td>
</tr>

@if ($canViewRisk && $t->risk_level !== \App\Models\TransactionRiskReview::LEVEL_SAFE)
<tr x-show="open" x-transition x-cloak class="bg-amber-50/50 border-b border-amber-100">
    <td :colspan="{{ $canViewRisk ? 9 : 8 }}" class="px-5 py-4">
        <div class="space-y-3">
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
                    <p class="text-sm text-slate-600">• {{ $reason }}</p>
                @endforeach
            </div>
            @endif

            <form method="POST" action="{{ route('internal.anomalies.review_status', $t->no_transaksi) }}" class="mt-3 flex flex-wrap items-center gap-2">
                @csrf @method('PATCH')
                <select name="review_status" class="ui-select text-sm py-1.5">
                    @foreach (\App\Models\TransactionRiskReview::REVIEW_STATUSES as $s)
                        <option value="{{ $s }}" @selected($t->review_status === $s)>
                            {{ \App\Models\TransactionRiskReview::reviewStatusLabel($s) }}
                        </option>
                    @endforeach
                </select>
                <button type="submit" class="ui-btn ui-btn-primary py-1.5 text-sm">Simpan</button>
                <button type="button" @click="open = false" class="ui-btn ui-btn-secondary py-1.5 text-sm">Tutup</button>
            </form>
        </div>
    </td>
</tr>
@endif
