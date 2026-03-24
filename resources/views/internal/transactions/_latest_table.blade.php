<div class="overflow-x-auto">
    <table class="min-w-full text-[10px] sm:text-sm">
        <thead>
            <tr class="bg-gray-50 text-left text-[11px] sm:text-xs font-semibold text-gray-400 uppercase tracking-wider border-b border-gray-100">
                <th class="px-2 sm:px-6 py-4">No. Transaksi</th>
                <th class="px-2 sm:px-6 py-4">Waktu</th>
                <th class="px-2 sm:px-6 py-4">Pembayar</th>
                <th class="px-2 sm:px-6 py-4 text-center">Kategori</th>
                <th class="px-2 sm:px-6 py-4 text-center">Bentuk</th>
                <th class="px-2 sm:px-6 py-4 text-right">Total Nominal</th>
                <th class="px-2 sm:px-6 py-4 text-center">Petugas</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @php $items = $transactions ?? $latestTransactions ?? []; @endphp
            @if (count($items) > 0)
                @foreach ($items as $t)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-2 sm:px-5 py-3 whitespace-nowrap">
                        <span class="font-mono text-xs font-semibold text-blue-600 bg-blue-50 px-1.5 py-1 rounded-md">{{ $t->no_transaksi }}</span>
                    </td>
                    <td class="px-2 py-3 text-gray-500 text-xs sm:text-sm whitespace-nowrap">
                        @if ($t->waktu_terima)
                            {{ $t->waktu_terima->timezone('Asia/Jakarta')->format('d/m/Y H:i') }}
                        @else
                            {{ $t->created_at->timezone('Asia/Jakarta')->format('d/m/Y H:i') }}
                        @endif
                    </td>
                    <td class="px-2 sm:px-5 py-3 whitespace-nowrap">
                        <div class="text-xs sm:text-sm font-semibold text-gray-700">{{ $t->pembayar_nama }}</div>
                        @if($t->muzakki_total > 1)
                            <div class="text-[9px] text-gray-500 mt-0.5">+ {{ $t->muzakki_total - 1 }} Muzakki Lainnya</div>
                        @endif
                    </td>
                    <td class="px-2 py-3">
                        <x-zakat-category-tags :categories="$t->categories_list" />
                    </td>
                    <td class="px-2 py-3">
                        <div class="flex flex-wrap gap-1 justify-center max-w-[100px] mx-auto">
                            @foreach(explode(',', $t->methods_list ?? '') as $met)
                                @if(trim($met))
                                    @php $label = \App\Models\ZakatTransaction::getMethodLabel(trim($met)); @endphp
                                    <span class="inline-flex items-center justify-center rounded px-1.5 py-1 text-[10px] sm:text-[11px] font-bold uppercase tracking-wide bg-amber-50 text-amber-700 border border-amber-100 whitespace-nowrap leading-tight text-center">{{ $label }}</span>
                                @endif
                            @endforeach
                        </div>
                    </td>
                    <td class="px-2 py-3 text-right whitespace-nowrap">
                        <div class="space-y-1">
                                @if($t->total_uang > 0)
                                    <div class="flex items-center justify-end gap-1.5">
                                        <div class="font-bold text-gray-800 text-xs sm:text-sm">{{ \App\Support\Format::rupiah((int)$t->total_uang) }}</div>
                                        @if($t->has_transfer)
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-black bg-emerald-100 text-emerald-700 border border-emerald-200 uppercase">TF</span>
                                        @endif
                                    </div>
                                @endif
                                @if($t->total_beras > 0)
                                    <div class="font-bold text-gray-800 text-xs sm:text-sm">{{ \App\Support\Format::kg((float)$t->total_beras) }}</div>
                                @endif
                        </div>
                    </td>
                    <td class="px-2 py-3 text-center text-gray-500 text-[10px] sm:text-sm whitespace-nowrap">
                        <div class="flex flex-col items-center gap-1 mx-auto w-fit">
                            <span class="font-medium font-sans text-gray-700 text-center">{{ $t->petugas?->name ?? '-' }}</span>
                            <span class="inline-flex items-center justify-center rounded px-2 py-0.5 text-[10px] font-bold uppercase bg-emerald-50 text-emerald-700 border border-emerald-100 whitespace-nowrap leading-tight text-center">
                                {{ \App\Models\ZakatTransaction::getShiftLabel($t->shift) }}
                            </span>
                        </div>
                    </td>
                </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center">
                        <div class="flex flex-col items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-gray-200 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <span class="text-sm font-medium text-gray-400">Belum ada transaksi ditemukan.</span>
                        </div>
                    </td>
                </tr>
            @endif
        </tbody>
    </table>
</div>
