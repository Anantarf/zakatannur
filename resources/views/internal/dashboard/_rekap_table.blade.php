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
            <tr class="border-b border-slate-200 bg-slate-50 text-left text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400 sm:text-xs">
                <th class="pl-4 pr-2 py-3 text-left">Kategori</th>
                <th class="px-2 py-3 text-center">Total Transaksi</th>
                <th class="px-2 py-3 text-right">Total Uang</th>
                <th class="pr-4 pl-2 py-3 text-right">Total Beras</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 font-medium text-slate-600">
            @foreach ($payload['items'] as $item)
                <tr class="group transition-colors hover:bg-brand-50/30 stagger-item">
                    <td class="pl-4 pr-2 py-2.5 font-bold uppercase text-slate-900 text-[12px]">
                        {{ \App\Models\ZakatTransaction::CATEGORY_LABELS[$item['category']] ?? strtoupper($item['category']) }}
                    </td>
                    <td class="px-2 py-2.5 text-center text-[13px]">{{ number_format($item['jumlah_transaksi']) }}</td>
                    <td class="whitespace-nowrap px-2 py-2.5 text-right font-semibold text-brand-700 text-[13px]">{{ $item['total_uang_display'] }}</td>
                    <td class="whitespace-nowrap pr-4 pl-2 py-2.5 text-right font-semibold text-amber-700 text-[13px]">{{ $item['total_beras_kg_display'] }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="border-t border-brand-100 bg-brand-50/60">
                <td class="pl-4 pr-2 py-3 font-bold text-brand-900 text-[13px]">GRAND TOTAL</td>
                <td class="px-2 py-3 text-center font-bold text-brand-900 text-[13px]">{{ number_format($payload['totals']['jumlah_transaksi']) }}</td>
                <td class="whitespace-nowrap px-2 py-3 text-right font-bold text-brand-900 text-[13px]">{{ $payload['totals']['total_uang_display'] }}</td>
                <td class="whitespace-nowrap pr-4 pl-2 py-3 text-right font-bold text-brand-900 text-[13px]">{{ $payload['totals']['total_beras_kg_display'] }}</td>
            </tr>
        </tfoot>
    </table>
</div>
