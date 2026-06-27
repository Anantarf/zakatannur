<div class="space-y-3 md:hidden">
    @foreach ($payload['items'] as $item)
        <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-md">
            <div class="flex items-start justify-between gap-3">
                <h4 class="text-sm font-bold uppercase text-slate-900">
                    {{ \App\Models\ZakatTransaction::CATEGORY_LABELS[$item['category']] ?? strtoupper($item['category']) }}
                </h4>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">
                    {{ number_format($item['jumlah_transaksi']) }} trx
                </span>
            </div>
            <div class="mt-3 grid grid-cols-2 gap-3 rounded-2xl bg-slate-50 p-3">
                <div class="space-y-1">
                    <p class="ui-label text-slate-400">Total Uang</p>
                    <p class="text-sm font-bold text-brand-700">{{ $item['total_uang_display'] }}</p>
                </div>
                <div class="space-y-1">
                    <p class="ui-label text-slate-400">Total Beras</p>
                    <p class="text-sm font-bold text-amber-700">{{ $item['total_beras_kg_display'] }}</p>
                </div>
            </div>
        </article>
    @endforeach

    <article class="rounded-2xl border border-brand-200 bg-brand-50/60 p-4 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-md">
        <p class="ui-label text-brand-700">Grand Total</p>
        <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-3">
            <div>
                <p class="ui-label text-brand-700/70">Transaksi</p>
                <p class="text-sm font-bold text-brand-900">{{ number_format($payload['totals']['jumlah_transaksi']) }}</p>
            </div>
            <div>
                <p class="ui-label text-brand-700/70">Total Uang</p>
                <p class="text-sm font-bold text-brand-900">{{ $payload['totals']['total_uang_display'] }}</p>
            </div>
            <div>
                <p class="ui-label text-brand-700/70">Total Beras</p>
                <p class="text-sm font-bold text-brand-900">{{ $payload['totals']['total_beras_kg_display'] }}</p>
            </div>
        </div>
    </article>
</div>

<div class="hidden overflow-x-auto md:block">
    <table class="min-w-full text-sm">
        <thead>
            <tr class="border-b border-slate-200 bg-slate-50 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 sm:text-sm">
                <th class="px-3 py-4 text-left sm:px-6 sm:py-4">Kategori</th>
                <th class="px-3 py-4 sm:px-6 sm:py-4 text-center">Total Transaksi</th>
                <th class="px-3 py-4 sm:px-6 sm:py-4 text-right">Total Uang</th>
                <th class="px-3 py-4 sm:px-6 sm:py-4 text-right">Total Beras</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 font-medium text-slate-600">
            @foreach ($payload['items'] as $item)
                <tr class="transition-colors hover:bg-brand-50/30">
                    <td class="px-3 py-4 font-bold uppercase text-slate-900 sm:px-6">
                        {{ \App\Models\ZakatTransaction::CATEGORY_LABELS[$item['category']] ?? strtoupper($item['category']) }}
                    </td>
                    <td class="px-3 py-4 text-center sm:px-6">{{ number_format($item['jumlah_transaksi']) }}</td>
                    <td class="whitespace-nowrap px-3 py-4 text-right font-semibold text-brand-700 sm:px-6">{{ $item['total_uang_display'] }}</td>
                    <td class="whitespace-nowrap px-3 py-4 text-right font-semibold text-amber-700 sm:px-6">{{ $item['total_beras_kg_display'] }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="border-t-2 border-brand-100 bg-brand-50/60 text-sm sm:text-base">
                <td class="px-3 py-4 font-bold text-brand-900 sm:px-6">GRAND TOTAL</td>
                <td class="px-3 py-4 text-center font-bold text-brand-900 sm:px-6">{{ number_format($payload['totals']['jumlah_transaksi']) }}</td>
                <td class="whitespace-nowrap px-3 py-4 text-right font-bold text-brand-900 sm:px-6">{{ $payload['totals']['total_uang_display'] }}</td>
                <td class="whitespace-nowrap px-3 py-4 text-right font-bold text-brand-900 sm:px-6">{{ $payload['totals']['total_beras_kg_display'] }}</td>
            </tr>
        </tfoot>
    </table>
</div>
