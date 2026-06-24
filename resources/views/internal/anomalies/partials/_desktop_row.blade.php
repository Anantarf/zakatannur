@php
    $groupTime = ($group->waktu_terima ?? $group->created_at)?->timezone('Asia/Jakarta');
@endphp
<tr class="transition-colors hover:bg-slate-50">
    <td class="whitespace-nowrap px-3 py-3 sm:px-5">
        <span class="rounded-md bg-slate-100 px-2 py-1 font-sans text-xs font-semibold text-slate-600">{{ $group->no_transaksi }}</span>
    </td>
    <td class="whitespace-nowrap px-3 py-3 text-[13px] text-slate-500 sm:px-5">
        <div class="leading-tight">
            <div>{{ $groupTime?->format('d/m/Y') }}</div>
            <div class="mt-1 text-[12px] text-slate-400">{{ $groupTime?->format('H:i') }}</div>
        </div>
    </td>
    <td class="px-3 py-3 sm:px-5">
        <div class="max-w-[180px] break-words text-sm font-semibold leading-tight text-slate-700">{{ $group->pembayar_nama }}</div>
        @if($group->flags_count > 1)
            <div class="mt-1 text-[11px] text-slate-400">+ {{ $group->flags_count - 1 }} flag lain</div>
        @endif
    </td>
    <td class="px-3 py-3 text-center">
        <x-zakat-category-tags :categories="$group->categories_list" />
    </td>
    <td class="px-3 py-3 text-center whitespace-nowrap">
        <div class="flex flex-col items-center gap-1">
            <x-risk-level-badge :level="$group->risk_level" />
        </div>
    </td>
    <td class="px-3 py-3 sm:px-5">
        <div class="max-w-[220px] text-sm leading-5 text-slate-600">{{ $group->primary_flag_label ?? '-' }}</div>
    </td>
    <td class="px-3 py-3 text-center whitespace-nowrap">
        <x-review-status-badge :status="$group->review_status" />
    </td>
    <td class="px-3 py-3 text-center text-[13px] text-slate-500">
        <div class="flex flex-col items-center gap-1">
            <span class="font-medium text-slate-700">{{ $group->petugas?->name ?? '-' }}</span>
            <span class="inline-flex items-center justify-center rounded px-2 py-0.5 text-[11px] font-bold uppercase leading-tight text-center whitespace-nowrap border border-brand-100 bg-brand-50 text-brand-700">
                {{ $group->shift_label }}
            </span>
        </div>
    </td>
    <td class="px-3 py-3 text-center whitespace-nowrap">
        <a href="{{ route('internal.anomalies.show', ['noTransaksi' => $group->no_transaksi]) }}" class="ui-icon-button ui-icon-button-amber px-2" title="Buka review" aria-label="Buka review">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <span class="ui-table-action-label">Review</span>
        </a>
    </td>
</tr>
