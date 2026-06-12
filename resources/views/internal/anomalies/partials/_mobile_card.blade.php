@php
    $groupTime = ($group->waktu_terima ?? $group->created_at)?->timezone('Asia/Jakarta');
@endphp
<article class="ui-mobile-card border-amber-100">
    <div class="flex items-start justify-between gap-3">
        <div class="space-y-1">
            <span class="inline-flex rounded-md bg-blue-50 px-2 py-1 font-sans text-xs font-semibold text-blue-600">{{ $group->no_transaksi }}</span>
            <h4 class="text-sm font-bold leading-tight text-slate-900">{{ $group->pembayar_nama }}</h4>
            <p class="text-xs text-slate-500">
                {{ $groupTime?->format('d/m/Y H:i') ?? '-' }}
                @if($group->flags_count > 1)
                    <span class="ml-1">+ {{ $group->flags_count - 1 }} flag lain</span>
                @endif
            </p>
        </div>
        <x-risk-level-badge :level="$group->risk_level" />
    </div>

    <div class="ui-mobile-meta-grid">
        <div class="ui-mobile-meta-item col-span-2">
            <p class="ui-mobile-meta-label">Kategori</p>
            <div class="mt-1">
                <x-zakat-category-tags :categories="$group->categories_list" />
            </div>
        </div>
        <div class="ui-mobile-meta-item">
            <p class="ui-mobile-meta-label">Review</p>
            <x-review-status-badge :status="$group->review_status" />
        </div>
        <div class="ui-mobile-meta-item">
            <p class="ui-mobile-meta-label">Petugas</p>
            <div class="ui-mobile-meta-value">{{ $group->petugas?->name ?? '-' }}</div>
            <span class="mt-1 inline-flex items-center justify-center rounded px-2 py-0.5 text-[11px] font-bold uppercase bg-emerald-50 text-emerald-700 border border-emerald-100 whitespace-nowrap leading-tight text-center">
                {{ $group->shift_label }}
            </span>
        </div>
        <div class="ui-mobile-meta-item col-span-2">
            <p class="ui-mobile-meta-label">Flag Utama</p>
            <p class="mt-1 text-sm leading-6 text-slate-600">{{ $group->primary_flag_label ?? '-' }}</p>
        </div>
    </div>

    <a href="{{ route('internal.anomalies.show', ['noTransaksi' => $group->no_transaksi]) }}" class="ui-btn ui-btn-secondary mt-4 w-full justify-center border-amber-200 text-amber-700 hover:bg-amber-50">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        Tinjau Kasus
    </a>
</article>