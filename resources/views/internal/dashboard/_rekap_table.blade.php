<div class="overflow-x-auto">
    <table class="min-w-full text-[10px] sm:text-sm">
        <thead>
            <tr class="bg-gray-50 text-left text-[11px] sm:text-xs font-bold text-gray-400 uppercase tracking-widest border-b border-gray-100">
                <th class="px-2 sm:px-6 py-3 sm:py-4 text-left">Kategori</th>
                <th class="px-2 sm:px-6 py-3 sm:py-4 text-center">Total Transaksi</th>
                <th class="px-2 sm:px-6 py-3 sm:py-4 text-right">Total Uang</th>
                <th class="px-2 sm:px-6 py-3 sm:py-4 text-right">Total Beras</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50 font-medium text-gray-600">
            @foreach ($payload['items'] as $item)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-2 sm:px-6 py-3 sm:py-4 font-bold text-gray-900 uppercase">
                        {{ \App\Models\ZakatTransaction::getCategoryLabel($item['category']) }}
                    </td>
                    <td class="px-2 sm:px-6 py-3 sm:py-4 text-center">{{ number_format($item['jumlah_transaksi']) }}</td>
                    <td class="px-2 sm:px-6 py-3 sm:py-4 text-right text-emerald-700 whitespace-nowrap">{{ $item['total_uang_display'] }}</td>
                    <td class="px-2 sm:px-6 py-3 sm:py-4 text-right text-amber-700 whitespace-nowrap">{{ $item['total_beras_kg_display'] }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="bg-emerald-50/50 border-t-2 border-emerald-100">
                <td class="px-2 sm:px-6 py-4 sm:py-5 text-[13px] sm:text-sm font-black text-emerald-900">GRAND TOTAL</td>
                <td class="px-2 sm:px-6 py-4 sm:py-5 text-center font-black text-emerald-900">{{ number_format($payload['totals']['jumlah_transaksi']) }}</td>
                <td class="px-2 sm:px-6 py-4 sm:py-5 text-right font-black text-emerald-900 whitespace-nowrap">{{ $payload['totals']['total_uang_display'] }}</td>
                <td class="px-2 sm:px-6 py-4 sm:py-5 text-right font-black text-emerald-900 whitespace-nowrap">{{ $payload['totals']['total_beras_kg_display'] }}</td>
            </tr>
        </tfoot>
    </table>
</div>
