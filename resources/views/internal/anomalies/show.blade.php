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
                <p class="text-sm text-slate-500">Tinjau sinyal sistem, verifikasi detail transaksi, lalu putuskan apakah kasus ini aman atau perlu tindak lanjut.</p>
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
        <div class="mx-auto max-w-6xl space-y-4 sm:space-y-5 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="ui-alert ui-alert-success">
                    <svg xmlns="http://www.w3.org/2000/svg" class="ui-alert-icon text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span class="font-medium">{{ session('status') }}</span>
                </div>
            @endif

            <x-form-errors />

            @php
                $riskLevelLabel = \App\Models\TransactionRiskReview::levelLabel($riskReview['risk_level'] ?? null);
                $reviewStatusLabel = \App\Models\TransactionRiskReview::reviewStatusLabel($riskReview['review_status'] ?? null);
                $hasDuplicateCandidates = !empty($riskReview['duplicate_candidates']);
            @endphp

            <div class="grid grid-cols-1 gap-4 xl:grid-cols-[minmax(0,1.4fr)_minmax(320px,0.6fr)]">
                <div class="space-y-4">
                    <div class="ui-card-strong overflow-hidden">
                        <div class="border-b border-slate-100/80 px-5 py-4 sm:px-6 sm:py-5">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                <div class="space-y-3">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <x-risk-level-badge :level="$riskReview['risk_level'] ?? null" />
                                        <x-review-status-badge :status="$riskReview['review_status'] ?? null" />
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Nomor Transaksi</p>
                                        <h3 class="mt-1 inline-flex rounded-lg border border-brand-100 bg-brand-50 px-3 py-1 font-sans text-sm font-bold text-brand-700 sm:text-base">{{ $noTransaksi }}</h3>
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Pembayar</p>
                                        <p class="mt-1 text-xl font-black leading-tight text-slate-800">{{ $mainTx->pembayar_nama }}</p>
                                    </div>
                                </div>

                                <div class="ui-panel-note lg:min-w-[260px]">
                                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Status Review</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-800">{{ $reviewStatusLabel }}</p>
                                    <p class="mt-3 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Sinyal Aktif</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-800">{{ $riskMeta['flags_count'] }} warning</p>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-4 border-b border-slate-100/80 bg-slate-50/70 px-5 py-4 sm:px-6 sm:py-5">
                            <section class="rounded-2xl border border-slate-200 bg-white p-4">
                                <div class="mb-4 space-y-1">
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Kenapa Ditandai</p>
                                    <h4 class="text-lg font-bold text-slate-900">{{ $riskMeta['flag_labels'][0] ?? 'Perlu dicek ulang' }}</h4>
                                </div>
                                @if (!empty($riskReview['reasons']))
                                    <ul class="space-y-3 text-sm text-slate-700">
                                        @foreach ($riskReview['reasons'] as $reason)
                                            <li class="flex items-start gap-3 rounded-xl border border-slate-100 bg-slate-50/60 px-4 py-3">
                                                <span class="mt-1.5 h-2 w-2 flex-shrink-0 rounded-full bg-amber-500"></span>
                                                <span class="leading-6">{{ $reason }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p class="text-sm text-slate-500">Belum ada alasan risiko yang tercatat untuk grup transaksi ini.</p>
                                @endif
                            </section>

                            @if ($hasDuplicateCandidates)
                                <section class="rounded-2xl border border-slate-200 bg-white p-4">
                                    <div class="mb-4 space-y-1">
                                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Kandidat Transaksi Mirip</p>
                                        <h4 class="text-lg font-bold text-slate-900">Bandingkan transaksi terdekat</h4>
                                    </div>
                                    <div class="space-y-3">
                                        @foreach ($riskReview['duplicate_candidates'] as $candidate)
                                            <div class="rounded-xl border border-slate-100 bg-slate-50/70 px-4 py-4">
                                                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                                    <div class="min-w-0">
                                                        <p class="font-sans text-xs font-semibold text-blue-600">{{ $candidate['no_transaksi'] ?? '-' }}</p>
                                                        <p class="mt-1 text-sm font-semibold text-slate-800">{{ $candidate['pembayar_nama'] ?? '-' }}</p>
                                                        <p class="text-xs text-slate-500">{{ $candidate['muzakki_name'] ?? '-' }}</p>
                                                    </div>
                                                    <div class="grid gap-2 text-left text-xs text-slate-500 sm:text-right">
                                                        <span class="rounded-full border border-slate-200 bg-white px-3 py-1 font-semibold text-slate-700">{{ $candidate['time_diff_minutes'] ?? 0 }} menit</span>
                                                        <span class="font-semibold text-slate-600">{{ \App\Models\TransactionRiskReview::flagLabel($candidate['match_type'] ?? null) }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </section>
                            @endif
                        </div>

                        <div class="px-5 py-4 sm:px-6 sm:py-5">
                            <div class="mb-4 space-y-1">
                                <div class="flex items-center gap-2">
                                    <span class="h-5 w-1 rounded-full bg-brand-500"></span>
                                    <h4 class="text-sm font-bold uppercase tracking-wide text-slate-800">Rincian Pembayaran</h4>
                                </div>
                            </div>
                            <div class="space-y-3 md:hidden">
                                @php $rowNo = 1; @endphp
                                @foreach ($groupedArr as $muzakkiName => $txsArr)
                                    @foreach ($txsArr as $tx)
                                            <article class="ui-mobile-card">
                                            <div class="flex items-start justify-between gap-3">
                                                <div class="min-w-0">
                                                    <div class="text-[10px] font-black uppercase tracking-widest text-slate-400">Muzakki {{ $rowNo++ }}</div>
                                                    <p class="mt-1 text-sm font-bold leading-tight text-slate-900">{{ $muzakkiName }}</p>
                                                </div>
                                                <span class="inline-flex items-center rounded px-2.5 py-1 text-[11px] font-bold uppercase tracking-wider {{ $tx->metode === 'beras' ? 'bg-amber-100 text-amber-700' : 'bg-brand-100 text-brand-700' }}">
                                                    {{ $tx->metode_label }}
                                                </span>
                                            </div>

                                            <div class="ui-mobile-meta-grid">
                                                <div class="ui-mobile-meta-item col-span-2">
                                                    <p class="ui-mobile-meta-label">Kategori</p>
                                                    <div class="mt-1">
                                                        <x-zakat-category-tags :categories="[$tx->category]" />
                                                    </div>
                                                </div>
                                                <div class="ui-mobile-meta-item">
                                                    <p class="ui-mobile-meta-label">Keterangan</p>
                                                    <div class="mt-1 text-right text-xs font-medium text-slate-500">
                                                        @if($tx->category === 'fitrah' && $tx->jiwa)
                                                            <span class="rounded-md border border-slate-100 bg-white px-2 py-1 font-bold text-slate-600">{{ $tx->jiwa }} Jiwa</span>
                                                        @elseif($tx->category === 'fidyah' && $tx->hari)
                                                            <span class="rounded-md border border-slate-100 bg-white px-2 py-1 font-bold text-slate-600">{{ $tx->hari }} Hari</span>
                                                        @else
                                                            -
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="ui-mobile-meta-item">
                                                    <p class="ui-mobile-meta-label">Nominal</p>
                                                    <div class="mt-1 text-right">
                                                        <p class="text-sm font-bold text-slate-900">
                                                            @if($tx->metode === 'beras')
                                                                {{ rtrim(rtrim(number_format($tx->jumlah_beras_kg, 2, ',', '.'), '0'), ',') }} <span class="ml-0.5 text-[9px] font-bold text-slate-400">kg</span>
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

                            <div class="hidden overflow-hidden rounded-xl border border-slate-100 bg-white shadow-sm md:block">
                                <table class="w-full table-fixed text-sm">
                                    <colgroup>
                                        <col class="w-[30%]">
                                        <col class="w-[22%]">
                                        <col class="w-[14%]">
                                        <col class="w-[14%]">
                                        <col class="w-[20%]">
                                    </colgroup>
                                    <thead>
                                        <tr class="border-b border-slate-100 bg-slate-50 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400 sm:text-xs">
                                            <th class="px-5 py-4">Nama Muzakki</th>
                                            <th class="px-3 py-4 sm:px-4">Kategori</th>
                                            <th class="px-3 py-4 sm:px-4">Bentuk</th>
                                            <th class="px-3 py-4 text-right sm:px-4">Keterangan</th>
                                            <th class="px-3 py-4 text-right sm:px-5">Nominal</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-50">
                                        @php $rowNo = 1; @endphp
                                        @foreach ($groupedArr as $muzakkiName => $txsArr)
                                            @php $txCount = count($txsArr); @endphp
                                            @foreach ($txsArr as $i => $tx)
                                                <tr class="transition-colors hover:bg-brand-50/30 {{ $i > 0 ? 'border-t border-dashed border-slate-100' : '' }}">
                                                    @if($i === 0)
                                                        <td class="px-4 py-4 align-top sm:px-5" rowspan="{{ $txCount }}">
                                                            <div class="flex items-start gap-3">
                                                                <span class="mt-0.5 min-w-[1.25rem] text-xs font-semibold text-slate-400">{{ $rowNo++ }}.</span>
                                                                <p class="break-words text-sm font-bold leading-tight text-slate-900">{{ $muzakkiName }}</p>
                                                            </div>
                                                        </td>
                                                    @endif
                                                    <td class="px-3 py-4 sm:px-4">
                                                        <x-zakat-category-tags :categories="[$tx->category]" />
                                                    </td>
                                                    <td class="px-3 py-4 sm:px-4">
                                                        <span class="inline-flex items-center rounded px-2.5 py-1 text-[11px] font-bold uppercase tracking-wider {{ $tx->metode === 'beras' ? 'bg-amber-100 text-amber-700' : 'bg-brand-100 text-brand-700' }}">
                                                            {{ $tx->metode_label }}
                                                        </span>
                                                    </td>
                                                    <td class="px-3 py-4 text-right text-xs font-medium text-slate-500 sm:px-4">
                                                        @if($tx->category === 'fitrah' && $tx->jiwa)
                                                            <span class="rounded-md border border-slate-100 bg-slate-50 px-2 py-1 font-bold text-slate-600">{{ $tx->jiwa }} Jiwa</span>
                                                        @elseif($tx->category === 'fidyah' && $tx->hari)
                                                            <span class="rounded-md border border-slate-100 bg-slate-50 px-2 py-1 font-bold text-slate-600">{{ $tx->hari }} Hari</span>
                                                        @else
                                                            -
                                                        @endif
                                                    </td>
                                                    <td class="px-3 py-4 text-right sm:px-5">
                                                        <p class="text-sm font-bold text-slate-900">
                                                            @if($tx->metode === 'beras')
                                                                <span class="whitespace-nowrap">{{ rtrim(rtrim(number_format($tx->jumlah_beras_kg, 2, ',', '.'), '0'), ',') }} <span class="ml-0.5 text-[10px] font-bold text-slate-400">kg</span></span>
                                                            @else
                                                                <span class="whitespace-nowrap">{{ \App\Support\Format::rupiah((int) $tx->nominal_uang) }}</span>
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

                <div class="space-y-4">
                    <div class="ui-card h-fit p-4 sm:p-5">
                        <h3 class="text-sm font-bold uppercase tracking-wide text-slate-800">Aksi Review</h3>
                        <div class="mb-4 mt-3 flex flex-wrap items-center gap-2">
                            <x-risk-level-badge :level="$riskReview['risk_level'] ?? null" />
                            <x-review-status-badge :status="$riskReview['review_status'] ?? null" />
                        </div>
                        <p class="text-sm leading-6 text-slate-600">{{ $riskReview['summary_text'] ?? 'Belum ada hasil analisis risiko untuk transaksi ini.' }}</p>

                        <div class="mt-4 space-y-3 rounded-xl border border-slate-100 bg-slate-50/70 p-4">
                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Status Kwitansi</p>
                                <p class="mt-1 text-sm font-semibold text-slate-700">
                                    {{ $receiptPrintedAt ? 'Sudah pernah dicetak' : 'Belum pernah dicetak' }}
                                </p>
                                @if ($receiptPrintedAt)
                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ $receiptPrintedAt->timezone('Asia/Jakarta')->format('d/m/Y H:i') }}
                                        @if ($receiptPrintedByName)
                                            oleh {{ $receiptPrintedByName }}
                                        @endif
                                    </p>
                                @endif
                            </div>

                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Sinyal Sistem</p>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    @forelse ($riskMeta['flag_labels'] as $flagLabel)
                                        <span class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">{{ $flagLabel }}</span>
                                    @empty
                                        <span class="text-sm text-slate-500">Belum ada flag aktif.</span>
                                    @endforelse
                                </div>
                            </div>

                            @if (!empty($riskReview['reviewed_at']) && !empty($riskReview['reviewed_by_name']))
                                <div>
                                    <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Review Terakhir</p>
                                    <p class="mt-1 text-sm text-slate-700">
                                        {{ $riskReview['reviewed_by_name'] }} pada {{ $riskReview['reviewed_at']->timezone('Asia/Jakarta')->format('d/m/Y H:i') }}
                                    </p>
                                </div>
                            @endif

                            @if (!empty($riskReview['review_note']))
                                <div>
                                    <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Catatan Operator</p>
                                    <p class="mt-1 rounded-xl border border-slate-200 bg-white px-3 py-3 text-sm leading-6 text-slate-700">{{ $riskReview['review_note'] }}</p>
                                </div>
                            @endif
                        </div>

                        <div class="ui-panel-note mt-5">
                            Keputusan review tidak mengubah transaksi asli. Gunakan catatan singkat agar operator lain paham alasan penutupan atau tindak lanjut kasus ini.
                        </div>

                        <form method="POST" action="{{ route('internal.anomalies.review_status', ['noTransaksi' => $noTransaksi]) }}" class="mt-5 space-y-3">
                            @csrf
                            @method('PATCH')
                            <select id="review_status" name="review_status" class="ui-select w-full bg-white">
                                @foreach ($reviewStatuses as $statusValue)
                                    <option value="{{ $statusValue }}" @selected(($riskReview['review_status'] ?? null) === $statusValue)>{{ \App\Models\TransactionRiskReview::reviewStatusLabel($statusValue) }}</option>
                                @endforeach
                            </select>
                            <div class="space-y-2">
                                <label for="review_note" class="text-xs font-semibold uppercase tracking-wider text-slate-500">Catatan Review</label>
                                <textarea id="review_note" name="review_note" rows="4" class="ui-textarea w-full bg-white" placeholder="Tuliskan alasan keputusan review. Wajib diisi jika status tindak lanjut.">{{ old('review_note', $riskReview['review_note'] ?? '') }}</textarea>
                            </div>
                            <button type="submit" class="ui-btn ui-btn-primary w-full">Simpan Keputusan Review</button>
                        </form>
                        <a href="{{ route('internal.transactions.edit', ['transaction' => $mainTx->id]) }}" class="ui-btn ui-btn-secondary mt-3 w-full">
                            Buka Ubah Transaksi
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
