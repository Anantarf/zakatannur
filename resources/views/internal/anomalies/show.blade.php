<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-1 text-center sm:text-left">
                <h2 class="ui-page-title">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 shrink-0 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z" />
                    </svg>
                    Detail Review Anomali
                </h2>
                <p class="text-sm text-slate-500">Nomor transaksi {{ $noTransaksi }} sedang ditinjau oleh admin.</p>
            </div>
            <div class="flex flex-col gap-2 sm:flex-row">
                <a href="{{ route('internal.transactions.show', ['transaction' => $mainTx->id]) }}" class="ui-btn ui-btn-secondary w-full sm:w-auto">
                    Buka Transaksi Asli
                </a>
                <a href="{{ route('internal.anomalies.index') }}" class="ui-btn ui-btn-secondary w-full sm:w-auto">
                    Kembali
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-6xl space-y-6 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="ui-alert ui-alert-success">
                    <svg xmlns="http://www.w3.org/2000/svg" class="ui-alert-icon text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span class="font-medium">{{ session('status') }}</span>
                </div>
            @endif

            <x-form-errors />

            <div class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1.3fr)_minmax(320px,0.7fr)]">
                <div class="space-y-6">
                    <div class="ui-card-strong overflow-hidden">
                        <div class="border-b border-gray-100/80 px-6 py-5 sm:px-8 sm:py-6">
                            <div class="mb-5 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <p class="mb-1 text-[10px] font-black uppercase tracking-[0.2em] text-gray-400">Nomor Transaksi</p>
                                    <h3 class="inline-block rounded-lg border border-emerald-100 bg-emerald-50 px-3 py-1 text-base font-bold text-emerald-700 sm:text-lg">{{ $noTransaksi }}</h3>
                                </div>
                                <div class="sm:text-right">
                                    <p class="mb-1 text-[10px] font-black uppercase tracking-[0.2em] text-gray-400">Pembayar</p>
                                    <p class="text-lg font-black leading-tight text-slate-800">{{ $mainTx->pembayar_nama }}</p>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                                <x-ui-stat-card title="Risk Level" :value="ucfirst($riskReview['risk_level'] ?? 'normal')" description="Severity terkuat pada grup ini." class="!rounded-xl !px-3 !py-3" />
                                <x-ui-stat-card title="Flag Terdeteksi" :value="$riskMeta['flags_count']" description="Jumlah pola yang aktif." class="!rounded-xl !px-3 !py-3" />
                                <x-ui-stat-card title="Status Review" :value="match($riskReview['review_status'] ?? null) { 'perlu_tindak_lanjut' => 'Tindak Lanjut', 'aman' => 'Aman', default => 'Belum Review', }" description="Keputusan review terbaru." class="!rounded-xl !px-3 !py-3 [&_.ui-stat-value]:!text-sm [&_.ui-stat-value]:!leading-tight" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 border-b border-gray-100/80 bg-slate-50/70 px-6 py-5 sm:px-8 sm:py-6 lg:grid-cols-2">
                            <div class="rounded-xl border border-gray-200 bg-white p-4">
                                <p class="mb-3 text-[11px] font-semibold text-gray-500">Alasan deteksi</p>
                                @if (!empty($riskReview['reasons']))
                                    <ul class="space-y-2 text-sm text-gray-700">
                                        @foreach ($riskReview['reasons'] as $reason)
                                            <li class="flex items-start gap-2">
                                                <span class="mt-1 h-1.5 w-1.5 flex-shrink-0 rounded-full bg-amber-500"></span>
                                                <span>{{ $reason }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p class="text-sm text-gray-500">Belum ada alasan risiko yang tercatat untuk grup transaksi ini.</p>
                                @endif
                            </div>

                            <div class="rounded-xl border border-gray-200 bg-white p-4">
                                <p class="mb-3 text-[11px] font-semibold text-gray-500">Kandidat transaksi mirip</p>
                                @if (!empty($riskReview['duplicate_candidates']))
                                    <div class="space-y-3">
                                        @foreach ($riskReview['duplicate_candidates'] as $candidate)
                                            <div class="rounded-lg border border-gray-100 bg-gray-50 px-3 py-2">
                                                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                                    <div>
                                                        <p class="font-mono text-xs font-semibold text-blue-600">{{ $candidate['no_transaksi'] ?? '-' }}</p>
                                                        <p class="text-sm font-semibold text-gray-700">{{ $candidate['pembayar_nama'] ?? '-' }}</p>
                                                        <p class="text-xs text-gray-500">{{ $candidate['muzakki_name'] ?? '-' }}</p>
                                                    </div>
                                                    <div class="text-left text-xs text-gray-500 sm:text-right">
                                                        <div>{{ $candidate['time_diff_minutes'] ?? 0 }} menit</div>
                                                        <div class="font-semibold text-gray-600">{{ str_replace('_', ' ', ucfirst($candidate['match_type'] ?? '')) }}</div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-sm text-gray-500">Belum ada kandidat transaksi mirip yang terdeteksi.</p>
                                @endif
                            </div>
                        </div>

                        <div class="px-6 py-5 sm:px-8 sm:py-6">
                            <div class="mb-4 flex items-center gap-2">
                                <span class="h-5 w-1 rounded-full bg-emerald-500"></span>
                                <h4 class="text-sm font-bold uppercase tracking-wide text-gray-800">Rincian Pembayaran</h4>
                            </div>
                            <div class="space-y-3 md:hidden">
                                @php $rowNo = 1; @endphp
                                @foreach ($groupedArr as $muzakkiName => $txsArr)
                                    @foreach ($txsArr as $tx)
                                            <article class="ui-mobile-card">
                                            <div class="flex items-start justify-between gap-3">
                                                <div class="min-w-0">
                                                    <div class="text-[10px] font-black uppercase tracking-widest text-gray-400">Muzakki {{ $rowNo++ }}</div>
                                                    <p class="mt-1 text-sm font-bold leading-tight text-gray-900">{{ $muzakkiName }}</p>
                                                </div>
                                                <span class="inline-flex items-center rounded px-2.5 py-1 text-[11px] font-bold uppercase tracking-wider {{ $tx->metode === 'beras' ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700' }}">
                                                    {{ $tx->metode_label }}
                                                </span>
                                            </div>

                                            <div class="ui-mobile-card-muted space-y-3">
                                                <div class="flex items-start justify-between gap-3">
                                                    <span class="text-[10px] font-black uppercase tracking-widest text-gray-400">Kategori</span>
                                                    <div class="max-w-[65%]">
                                                        <x-zakat-category-tags :categories="[$tx->category]" />
                                                    </div>
                                                </div>
                                                <div class="flex items-start justify-between gap-3">
                                                    <span class="text-[10px] font-black uppercase tracking-widest text-gray-400">Keterangan</span>
                                                    <div class="text-right text-xs font-medium text-gray-500">
                                                        @if($tx->category === 'fitrah' && $tx->jiwa)
                                                            <span class="rounded-md border border-gray-100 bg-white px-2 py-1 font-bold text-gray-600">{{ $tx->jiwa }} Jiwa</span>
                                                        @elseif($tx->category === 'fidyah' && $tx->hari)
                                                            <span class="rounded-md border border-gray-100 bg-white px-2 py-1 font-bold text-gray-600">{{ $tx->hari }} Hari</span>
                                                        @else
                                                            -
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="flex items-start justify-between gap-3">
                                                    <span class="text-[10px] font-black uppercase tracking-widest text-gray-400">Nominal</span>
                                                    <div class="text-right">
                                                        <p class="text-sm font-bold text-gray-900">
                                                            @if($tx->metode === 'beras')
                                                                {{ rtrim(rtrim(number_format($tx->jumlah_beras_kg, 2, ',', '.'), '0'), ',') }} <span class="ml-0.5 text-[9px] font-bold text-gray-400">kg</span>
                                                            @else
                                                                {{ \App\Support\Format::rupiah((int) $tx->nominal_uang) }}
                                                                @if($tx->is_transfer)
                                                                    <x-transfer-badge class="ml-1" />
                                                                @endif
                                                            @endif
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </article>
                                    @endforeach
                                @endforeach
                            </div>

                            <div class="hidden overflow-x-auto rounded-xl border border-gray-100 bg-white shadow-sm md:block">
                                <table class="min-w-full text-sm">
                                    <thead>
                                        <tr class="border-b border-gray-100 bg-gray-50 text-left text-[11px] font-semibold uppercase tracking-wider text-gray-400 sm:text-xs">
                                            <th class="px-6 py-4">Nama Muzakki</th>
                                            <th class="px-3 py-4 sm:px-6">Kategori</th>
                                            <th class="px-3 py-4 sm:px-6">Bentuk</th>
                                            <th class="px-3 py-4 text-right sm:px-6">Keterangan</th>
                                            <th class="px-3 py-4 text-right sm:px-6">Nominal</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50">
                                        @php $rowNo = 1; @endphp
                                        @foreach ($groupedArr as $muzakkiName => $txsArr)
                                            @php $txCount = count($txsArr); @endphp
                                            @foreach ($txsArr as $i => $tx)
                                                <tr class="transition-colors hover:bg-emerald-50/30 {{ $i > 0 ? 'border-t border-dashed border-gray-100' : '' }}">
                                                    @if($i === 0)
                                                        <td class="px-3 py-4 align-top sm:px-6" rowspan="{{ $txCount }}">
                                                            <div class="flex items-start gap-3">
                                                                <span class="mt-0.5 min-w-[1.25rem] text-xs font-semibold text-gray-400">{{ $rowNo++ }}.</span>
                                                                <p class="text-sm font-bold leading-tight text-gray-900">{{ $muzakkiName }}</p>
                                                            </div>
                                                        </td>
                                                    @endif
                                                    <td class="px-3 py-4 sm:px-6">
                                                        <x-zakat-category-tags :categories="[$tx->category]" />
                                                    </td>
                                                    <td class="px-3 py-4 sm:px-6">
                                                        <span class="inline-flex items-center rounded px-2.5 py-1 text-[11px] font-bold uppercase tracking-wider {{ $tx->metode === 'beras' ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700' }}">
                                                            {{ $tx->metode_label }}
                                                        </span>
                                                    </td>
                                                    <td class="px-3 py-4 text-right text-xs font-medium text-gray-500 sm:px-6">
                                                        @if($tx->category === 'fitrah' && $tx->jiwa)
                                                            <span class="rounded-md border border-gray-100 bg-gray-50 px-2 py-1 font-bold text-gray-600">{{ $tx->jiwa }} Jiwa</span>
                                                        @elseif($tx->category === 'fidyah' && $tx->hari)
                                                            <span class="rounded-md border border-gray-100 bg-gray-50 px-2 py-1 font-bold text-gray-600">{{ $tx->hari }} Hari</span>
                                                        @else
                                                            -
                                                        @endif
                                                    </td>
                                                    <td class="px-2 py-4 text-right sm:px-6">
                                                        <p class="whitespace-nowrap text-[10px] font-bold text-gray-900 sm:text-sm">
                                                            @if($tx->metode === 'beras')
                                                                {{ rtrim(rtrim(number_format($tx->jumlah_beras_kg, 2, ',', '.'), '0'), ',') }} <span class="ml-0.5 text-[9px] font-bold text-gray-400 sm:text-xs">kg</span>
                                                            @else
                                                                {{ \App\Support\Format::rupiah((int) $tx->nominal_uang) }}
                                                                @if($tx->is_transfer)
                                                                    <x-transfer-badge class="ml-1" />
                                                                @endif
                                                            @endif
                                                        </p>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="ui-card p-5 sm:p-6">
                        <div class="mb-4 flex flex-wrap items-center gap-2">
                            <x-risk-level-badge :level="$riskReview['risk_level'] ?? null" />
                            <x-review-status-badge :status="$riskReview['review_status'] ?? null" />
                        </div>
                        <p class="text-sm leading-6 text-gray-600">{{ $riskReview['summary_text'] ?? 'Belum ada hasil analisis risiko untuk transaksi ini.' }}</p>

                        <div class="mt-5 space-y-3 rounded-xl border border-gray-100 bg-slate-50/70 p-4">
                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400">Status Kwitansi</p>
                                <p class="mt-1 text-sm font-semibold text-gray-700">
                                    {{ $receiptPrintedAt ? 'Sudah pernah dicetak' : 'Belum pernah dicetak' }}
                                </p>
                                @if ($receiptPrintedAt)
                                    <p class="mt-1 text-xs text-gray-500">
                                        {{ $receiptPrintedAt->timezone('Asia/Jakarta')->format('d/m/Y H:i') }}
                                        @if ($receiptPrintedByName)
                                            oleh {{ $receiptPrintedByName }}
                                        @endif
                                    </p>
                                @endif
                            </div>

                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400">Flag Aktif</p>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    @forelse ($riskMeta['flag_labels'] as $flagLabel)
                                        <span class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">{{ $flagLabel }}</span>
                                    @empty
                                        <span class="text-sm text-gray-500">Belum ada flag aktif.</span>
                                    @endforelse
                                </div>
                            </div>

                            @if (!empty($riskReview['reviewed_at']) && !empty($riskReview['reviewed_by_name']))
                                <div>
                                    <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400">Review Terakhir</p>
                                    <p class="mt-1 text-sm text-gray-700">
                                        {{ $riskReview['reviewed_by_name'] }} pada {{ $riskReview['reviewed_at']->timezone('Asia/Jakarta')->format('d/m/Y H:i') }}
                                    </p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="ui-card p-5 sm:p-6">
                        <h3 class="text-sm font-bold uppercase tracking-wide text-gray-800">Keputusan Review</h3>
                        <p class="mt-1 text-sm text-slate-500">Gunakan `Aman` bila transaksi memang sah dan tidak janggal. Gunakan `Tindak Lanjut` bila Anda akan langsung membetulkan atau menyesuaikan transaksi ini.</p>
                        <form method="POST" action="{{ route('internal.anomalies.review_status', ['noTransaksi' => $noTransaksi]) }}" class="mt-5 space-y-3">
                            @csrf
                            @method('PATCH')
                            <select id="review_status" name="review_status" class="ui-select w-full bg-white">
                                @foreach ($reviewStatuses as $statusValue)
                                    <option value="{{ $statusValue }}" @selected(($riskReview['review_status'] ?? null) === $statusValue)>{{ str_replace('_', ' ', ucfirst($statusValue)) }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="ui-btn ui-btn-primary w-full">Simpan Status Review</button>
                        </form>
                        <a href="{{ route('internal.transactions.edit', ['transaction' => $mainTx->id]) }}" class="ui-btn ui-btn-secondary mt-3 w-full">
                            Edit Transaksi Untuk Tindak Lanjut
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
