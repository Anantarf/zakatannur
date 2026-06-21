@php $items = $transactions ?? $latestTransactions ?? []; @endphp

<div class="space-y-3 md:hidden">
    @if (count($items) > 0)
        @foreach ($items as $t)
            <article class="rounded-card border border-slate-100 bg-white p-4 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <span class="inline-flex rounded-md bg-slate-100 px-2 py-1 font-sans text-[11px] font-semibold text-slate-600">{{ $t->no_transaksi }}</span>
                        <div class="mt-2 text-sm font-semibold leading-tight text-slate-800">{{ $t->pembayar_nama }}</div>
                        @if($t->muzakki_total > 1)
                            <div class="mt-1 text-[11px] text-slate-500">+ {{ $t->muzakki_total - 1 }} muzakki lainnya</div>
                        @endif
                    </div>
                    <div class="shrink-0 text-right">
                        <div class="text-[11px] font-semibold text-slate-500">
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
                        <span class="text-[10px] font-semibold uppercase tracking-[0.12em] text-slate-400">Kategori</span>
                        <div class="max-w-[65%]">
                            <x-zakat-category-tags :categories="$t->categories_list" />
                        </div>
                    </div>
                    <div class="flex items-start justify-between gap-3">
                        <span class="text-[10px] font-semibold uppercase tracking-[0.12em] text-slate-400">Bentuk</span>
                        <div class="flex max-w-[65%] flex-wrap justify-end gap-1">
                            @foreach(explode(',', $t->methods_list ?? '') as $met)
                                @php $met = trim($met); @endphp
                                @if($met)
                                    <span class="inline-flex items-center justify-center rounded px-1.5 py-1 text-[10px] font-bold uppercase tracking-wide bg-amber-50 text-amber-700 border border-amber-100 whitespace-nowrap leading-tight text-center">{{ \App\Models\ZakatTransaction::METHOD_LABELS[$met] ?? strtoupper($met) }}</span>
                                @endif
                            @endforeach
                        </div>
                    </div>
                    <div class="flex items-start justify-between gap-3">
                        <span class="text-[10px] font-semibold uppercase tracking-[0.12em] text-slate-400">Nominal</span>
                        <div class="text-right">
                            @if($t->total_uang > 0)
                                <div class="flex items-center justify-end gap-1.5">
                                    <div class="text-sm font-bold text-slate-800">{{ $t->total_uang_display }}</div>
                                    @if($t->has_transfer)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-brand-100 text-brand-700 border border-brand-200 uppercase">TF</span>
                                    @endif
                                </div>
                            @endif
                            @if($t->total_beras > 0)
                                <div class="mt-1 text-sm font-bold text-slate-800">{{ $t->total_beras_display }}</div>
                            @endif
                        </div>
                    </div>
                    <div class="flex items-start justify-between gap-3">
                        <span class="text-[10px] font-semibold uppercase tracking-[0.12em] text-slate-400">Petugas</span>
                        <div class="text-right">
                            <div class="text-sm font-medium text-slate-700">{{ $t->petugas?->name ?? '-' }}</div>
                            <span class="mt-1 inline-flex items-center justify-center rounded px-2 py-0.5 text-[10px] font-bold uppercase bg-brand-50 text-brand-700 border border-brand-100 whitespace-nowrap leading-tight text-center">
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
    <table class="min-w-full text-[10px] sm:text-sm">
        <thead>
            <tr class="border-b border-slate-100 bg-slate-50 text-left text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400 sm:text-xs">
                <th class="px-3 py-3 sm:px-5">No. Transaksi</th>
                <th class="px-3 py-3 sm:px-5">Waktu</th>
                <th class="px-3 py-3 sm:px-5">Pembayar</th>
                <th class="px-3 py-3 sm:px-5 text-center">Kategori</th>
                <th class="px-3 py-3 sm:px-5 text-center">Bentuk</th>
                <th class="px-3 py-3 sm:px-5 text-right">Total Nominal</th>
                <th class="px-3 py-3 sm:px-5 text-center">Petugas</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @if (count($items) > 0)
                @foreach ($items as $t)
                <tr class="transition-colors hover:bg-brand-50/30">
                    <td class="whitespace-nowrap px-3 py-3 sm:px-5">
                        <span class="font-sans text-xs font-semibold text-slate-600 bg-slate-100 px-1.5 py-1 rounded-md">{{ $t->no_transaksi }}</span>
                    </td>
                    <td class="whitespace-nowrap px-3 py-3 text-xs text-slate-500 sm:text-sm">
                        @if ($t->waktu_terima)
                            {{ $t->waktu_terima->timezone('Asia/Jakarta')->format('d/m/Y H:i') }}
                        @else
                            {{ $t->created_at->timezone('Asia/Jakarta')->format('d/m/Y H:i') }}
                        @endif
                    </td>
                    <td class="whitespace-nowrap px-3 py-3 sm:px-5">
                        <div class="text-xs font-semibold text-slate-700 sm:text-sm">{{ $t->pembayar_nama }}</div>
                        @if($t->muzakki_total > 1)
                            <div class="mt-0.5 text-[10px] text-slate-500">+ {{ $t->muzakki_total - 1 }} Muzakki Lainnya</div>
                        @endif
                    </td>
                    <td class="px-3 py-3">
                        <x-zakat-category-tags :categories="$t->categories_list" />
                    </td>
                    <td class="px-3 py-3">
                        <div class="flex flex-wrap gap-1 justify-center max-w-[100px] mx-auto">
                            @foreach(explode(',', $t->methods_list ?? '') as $met)
                                @php $met = trim($met); @endphp
                                @if($met)
                                    <span class="inline-flex items-center justify-center rounded px-1.5 py-1 text-[10px] sm:text-[11px] font-bold uppercase tracking-wide bg-amber-50 text-amber-700 border border-amber-100 whitespace-nowrap leading-tight text-center">{{ \App\Models\ZakatTransaction::METHOD_LABELS[$met] ?? strtoupper($met) }}</span>
                                @endif
                            @endforeach
                        </div>
                    </td>
                    <td class="whitespace-nowrap px-3 py-3 text-right">
                        <div class="space-y-1">
                                @if($t->total_uang > 0)
                                    <div class="flex items-center justify-end gap-1.5">
                                        <div class="text-xs font-bold text-slate-800 sm:text-sm">{{ $t->total_uang_display }}</div>
                                        @if($t->has_transfer)
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-brand-100 text-brand-700 border border-brand-200 uppercase">TF</span>
                                        @endif
                                    </div>
                                @endif
                                @if($t->total_beras > 0)
                                    <div class="text-xs font-bold text-slate-800 sm:text-sm">{{ $t->total_beras_display }}</div>
                                @endif
                        </div>
                    </td>
                    <td class="whitespace-nowrap px-3 py-3 text-center text-[10px] text-slate-500 sm:text-sm">
                        <div class="flex flex-col items-center gap-1 mx-auto w-fit">
                            <span class="text-center font-medium font-sans text-slate-700">{{ $t->petugas?->name ?? '-' }}</span>
                            <span class="inline-flex items-center justify-center rounded px-2 py-0.5 text-[10px] font-bold uppercase bg-brand-50 text-brand-700 border border-brand-100 whitespace-nowrap leading-tight text-center">
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
