<tr class="group transition-colors hover:bg-brand-50/30 stagger-item">
    <td class="whitespace-nowrap pl-4 pr-2 py-2 sm:py-2.5">
        <span class="-ml-1.5 font-sans text-[11px] font-bold text-slate-700 transition-colors group-hover:text-brand-700 bg-slate-100 group-hover:bg-brand-100/50 px-1.5 py-0.5 rounded-md">{!! \App\Support\Format::highlight($t->no_transaksi, $q) !!}</span>
    </td>
    <td class="whitespace-nowrap px-2 py-2 sm:py-2.5">
        @if ($t->waktu_terima)
            <div class="leading-none">
                <div class="font-bold text-[13px] text-slate-800">{{ $t->waktu_terima->timezone('Asia/Jakarta')->format('d/m/y') }}</div>
                <div class="mt-1 text-[11px] font-medium text-slate-500">{{ $t->waktu_terima->timezone('Asia/Jakarta')->format('H:i') }}</div>
            </div>
        @else
            <div class="leading-none">
                <div class="font-bold text-[13px] text-slate-800">{{ $t->created_at->timezone('Asia/Jakarta')->format('d/m/y') }}</div>
                <div class="mt-1 text-[11px] font-medium text-slate-500">{{ $t->created_at->timezone('Asia/Jakarta')->format('H:i') }}</div>
            </div>
        @endif
    </td>
    <td class="px-2 py-2 sm:py-2.5">
        <div class="max-w-[140px] whitespace-normal break-words text-[13px] font-bold leading-tight text-slate-800">{!! \App\Support\Format::highlight($t->pembayar_nama, $q) !!}</div>
        @if($t->muzakki_total > 1)
            <div class="mt-0.5 whitespace-nowrap text-[10px] text-slate-500 font-medium">+ {{ $t->muzakki_total - 1 }} Lainnya</div>
        @endif
    </td>
    <td class="px-1 py-2 text-center">
        <x-zakat-category-tags :categories="$t->categories_list" />
    </td>
    <td class="px-1 py-2 text-center">
        <div class="flex flex-wrap gap-1 justify-center max-w-[80px] mx-auto">
            @foreach(explode(',', $t->methods_list ?? '') as $met)
                @php $met = trim($met); @endphp
                @if($met)
                    <span class="ui-badge ui-badge-token ui-badge-token-amber">{{ \App\Models\ZakatTransaction::METHOD_LABELS[$met] ?? strtoupper($met) }}</span>
                @endif
            @endforeach
        </div>
    </td>
    <td class="whitespace-nowrap px-2 py-2 text-right sm:py-2.5">
        <div class="space-y-0.5">
            @if($t->total_uang > 0)
                <div class="flex items-center justify-end gap-1 text-[13px] font-semibold text-slate-800">
                    {{ $t->total_uang_display }}
                    @if($t->has_transfer)
                        <x-transfer-badge />
                    @else
                        <x-cash-badge />
                    @endif
                </div>
            @endif
            @if($t->total_beras > 0)
                <div class="text-[13px] font-semibold text-slate-800">{{ $t->total_beras_display }}</div>
            @endif
        </div>
    </td>
    @if ($canViewRisk)
        <td class="whitespace-nowrap px-1 py-2 text-center">
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
    <td class="whitespace-nowrap px-2 py-2 text-center sm:py-2.5">
        <div class="flex flex-col items-center gap-1">
            <span class="font-medium text-slate-700 text-[12px]">{{ $t->petugas?->name ?? '-' }}</span>
            <span class="ui-badge ui-badge-token ui-badge-token-emerald">{{ $t->shift_label }}</span>
        </div>
    </td>
    <td class="whitespace-nowrap pl-2 pr-4 py-2 text-center sm:py-2.5">
        <div class="flex items-center justify-center gap-1.5">
            <a class="ui-btn ui-btn-secondary px-2 py-1.5 text-slate-500 hover:text-brand-600 hover:bg-brand-50" href="{{ route('internal.transactions.show', ['transaction' => $t->id]) }}" title="Lihat">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
            </a>
            <a class="ui-btn ui-btn-secondary px-2 py-1.5 text-slate-500 hover:text-blue-600 hover:bg-blue-50" href="{{ route('internal.transactions.receipt', ['transaction' => $t->id]) }}" target="_blank" rel="noopener" title="Cetak">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
            </a>
            <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                <button type="button" @click="open = !open" class="ui-btn ui-btn-secondary px-2 py-1.5 text-slate-500 hover:text-slate-800 focus:ring-brand-500" title="Opsi lainnya">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                    </svg>
                </button>
                <div x-show="open" 
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="transform opacity-0 scale-95"
                     x-transition:enter-end="transform opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-75"
                     x-transition:leave-start="transform opacity-100 scale-100"
                     x-transition:leave-end="transform opacity-0 scale-95"
                     class="absolute right-0 mt-1.5 w-36 bg-white border border-slate-200 rounded-xl shadow-xl shadow-slate-200/50 z-50 overflow-hidden py-1"
                     x-cloak>
                    @can('update', $t)
                        <a href="{{ route('internal.transactions.edit', ['transaction' => $t->id]) }}" class="flex items-center gap-2 w-full px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:text-brand-600 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                            Ubah
                        </a>
                        <button type="button" @click="$dispatch('open-modal', 'trash-modal'); $dispatch('open-trash-modal', { id: {{ $t->id }}, no: '{{ addslashes($t->no_transaksi) }}' }); open = false;" class="flex items-center gap-2 w-full text-left px-4 py-2 text-xs font-semibold text-red-600 hover:bg-red-50 transition-colors border-t border-slate-100">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                            Hapus
                        </button>
                    @else
                        <button type="button" @click="$dispatch('open-modal', 'restricted-modal'); open = false;" class="flex items-center gap-2 w-full text-left px-4 py-2 text-xs font-semibold text-slate-400 hover:bg-slate-50 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                            Ubah
                        </button>
                        <button type="button" @click="$dispatch('open-modal', 'restricted-modal'); open = false;" class="flex items-center gap-2 w-full text-left px-4 py-2 text-xs font-semibold text-slate-400 hover:bg-red-50 hover:text-red-600 transition-colors border-t border-slate-100">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                            Hapus
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
                    <span class="ui-badge ui-badge-risk ui-badge-risk-warning rounded-full px-3 py-1">{{ \App\Models\TransactionRiskReview::flagLabel($flag) }}</span>
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
                @php
                    $revOptions = [];
                    foreach (\App\Models\TransactionRiskReview::REVIEW_STATUSES as $s) {
                        $revOptions[$s] = \App\Models\TransactionRiskReview::reviewStatusLabel($s);
                    }
                @endphp
                <x-ui-select-custom name="review_status" :options="$revOptions" :value="$t->review_status" class="w-48" />
                <button type="submit" class="ui-btn ui-btn-primary py-1.5 text-sm">Simpan</button>
                <button type="button" @click="open = false" class="ui-btn ui-btn-secondary py-1.5 text-sm">Tutup</button>
            </form>
        </div>
    </td>
</tr>
@endif
