@php $items = $transactions ?? $latestTransactions ?? []; @endphp

<div class="space-y-3 md:hidden">
    @if (count($items) > 0)
        @foreach ($items as $t)
            <article class="rounded-card border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <span class="inline-flex rounded-md bg-slate-100 px-2 py-1 font-sans font-semibold text-slate-600" style="font-size: var(--text-caption);">{{ $t->no_transaksi }}</span>
                        <div class="mt-2 text-sm font-semibold leading-tight text-slate-800">{{ $t->pembayar_nama }}</div>
                        @if($t->muzakki_total > 1)
                            <div class="mt-1 text-slate-500" style="font-size: var(--text-caption);">+ {{ $t->muzakki_total - 1 }} muzakki lainnya</div>
                        @endif
                    </div>
                    <div class="shrink-0 text-right">
                        <div class="font-semibold text-slate-500" style="font-size: var(--text-caption);">
                            @if ($t->waktu_terima)
                                {{ $t->waktu_terima->timezone('Asia/Jakarta')->format('d/m/Y') }}
                            @else
                                {{ $t->created_at->timezone('Asia/Jakarta')->format('d/m/Y') }}
                            @endif
                        </div>
                        <div class="mt-1 text-xs font-bold text-slate-700">
                            @if ($t->waktu_terima)
                                {{ $t->waktu_terima->timezone('Asia/Jakarta')->format('H:i') }}
                            @else
                                {{ $t->created_at->timezone('Asia/Jakarta')->format('H:i') }}
                            @endif
                        </div>
                    </div>
                </div>

                <div class="mt-3 grid grid-cols-1 gap-3 rounded-xl bg-slate-50 px-3 py-3">
                    <div class="flex items-start justify-between gap-3">
                        <span class="font-semibold uppercase text-slate-400" style="font-size: var(--text-eyebrow); letter-spacing: var(--tracking-eyebrow);">Kategori</span>
                        <div class="max-w-[65%]">
                            <x-zakat-category-tags :categories="$t->categories_list" />
                        </div>
                    </div>
                    <div class="flex items-start justify-between gap-3">
                        <span class="font-semibold uppercase text-slate-400" style="font-size: var(--text-eyebrow); letter-spacing: var(--tracking-eyebrow);">Bentuk</span>
                        <div class="flex max-w-[65%] flex-wrap justify-end gap-1">
                            @foreach(explode(',', $t->methods_list ?? '') as $met)
                                @php $met = trim($met); @endphp
                                @if($met)
                                    <span class="inline-flex items-center justify-center rounded px-1.5 py-1 font-bold uppercase bg-amber-50 text-amber-700 border border-amber-100 whitespace-nowrap leading-tight text-center" style="font-size: var(--text-eyebrow); letter-spacing: var(--tracking-eyebrow);">{{ \App\Models\ZakatTransaction::METHOD_LABELS[$met] ?? strtoupper($met) }}</span>
                                @endif
                            @endforeach
                        </div>
                    </div>
                    <div class="flex items-start justify-between gap-3">
                        <span class="font-semibold uppercase text-slate-400" style="font-size: var(--text-eyebrow); letter-spacing: var(--tracking-eyebrow);">Nominal</span>
                        <div class="text-right">
                            @if($t->total_uang > 0)
                                <div class="flex items-center justify-end gap-1.5">
                                    <div class="text-sm font-bold text-slate-800">{{ $t->total_uang_display }}</div>
                                    @if($t->has_transfer)
                                        <x-transfer-badge />
                                    @else
                                        <x-cash-badge />
                                    @endif
                                </div>
                            @endif
                            @if($t->total_beras > 0)
                                <div class="mt-1 text-sm font-bold text-slate-800">{{ $t->total_beras_display }}</div>
                            @endif
                        </div>
                    </div>
                    <div class="flex items-start justify-between gap-3">
                        <span class="font-semibold uppercase text-slate-400" style="font-size: var(--text-eyebrow); letter-spacing: var(--tracking-eyebrow);">Petugas</span>
                        <div class="text-right">
                            <div class="text-sm font-medium text-slate-700">{{ $t->petugas?->name ?? '-' }}</div>
                            <span class="mt-1 inline-flex items-center justify-center rounded px-2 py-0.5 font-bold uppercase bg-brand-50 text-brand-700 border border-brand-100 whitespace-nowrap leading-tight text-center" style="font-size: var(--text-eyebrow);">
                                {{ $t->shift_label }}
                            </span>
                        </div>
                    </div>
                </div>
            </article>
        @endforeach
    @else
        <div class="px-6 py-12 text-center">
            <div class="flex flex-col items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="mb-2 h-10 w-10 text-slate-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <span class="text-sm font-medium text-slate-400">Belum ada transaksi ditemukan.</span>
            </div>
        </div>
    @endif
</div>

<div class="hidden overflow-x-auto md:block">
    <table class="min-w-full text-sm">
        <thead>
            <tr class="border-b border-slate-200 bg-slate-50 text-left text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400 sm:text-xs">
                <th class="pl-4 pr-2 py-3">No. Transaksi</th>
                <th class="px-2 py-3">Waktu</th>
                <th class="px-2 py-3">Pembayar</th>
                <th class="px-1 py-3 text-center">Kategori</th>
                <th class="px-1 py-3 text-center">Bentuk</th>
                <th class="px-2 py-3 text-right">Total Nominal</th>
                <th class="pr-4 pl-2 py-3 text-center">Petugas</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @if (count($items) > 0)
                @foreach ($items as $t)
                <tr class="group transition-colors hover:bg-brand-50/30 stagger-item">
                    <td class="whitespace-nowrap pl-4 pr-2 py-2 sm:py-2.5">
                        <span class="-ml-1.5 font-sans text-[11px] font-bold text-slate-700 transition-colors group-hover:text-brand-700 bg-slate-100 group-hover:bg-brand-100/50 px-1.5 py-0.5 rounded-md">{{ $t->no_transaksi }}</span>
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
                        <div class="max-w-[140px] whitespace-normal break-words text-[13px] font-bold leading-tight text-slate-800">{{ $t->pembayar_nama }}</div>
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
                                    <span class="inline-flex items-center justify-center rounded px-1.5 py-0.5 text-[9px] font-bold uppercase bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-600/20 whitespace-nowrap leading-tight">{{ \App\Models\ZakatTransaction::METHOD_LABELS[$met] ?? strtoupper($met) }}</span>
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
                    <td class="whitespace-nowrap pr-4 pl-2 py-2 text-center sm:py-2.5">
                        <div class="flex flex-col items-center gap-1">
                            <span class="font-medium text-slate-700 text-[12px]">{{ $t->petugas?->name ?? '-' }}</span>
                            <span class="inline-flex items-center justify-center rounded px-1.5 py-0.5 text-[9px] font-bold uppercase bg-brand-50 text-brand-700 ring-1 ring-inset ring-brand-600/20 whitespace-nowrap leading-tight">
                                {{ $t->shift_label }}
                            </span>
                        </div>
                    </td>
                </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center">
                        <div class="flex flex-col items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-slate-200 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <span class="text-sm font-medium text-slate-400">Belum ada transaksi ditemukan.</span>
                        </div>
                    </td>
                </tr>
            @endif
        </tbody>
    </table>
</div>
