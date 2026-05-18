@php
    $riskReview = $riskReview ?? [];
@endphp

<div class="border-b border-gray-100/80 bg-slate-50/70 px-6 py-5 sm:px-8 sm:py-6">
    <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
        <div class="space-y-3">
            <div class="ui-section-title">
                <span class="ui-section-accent !bg-amber-500"></span>
                <h4 class="text-sm font-bold text-gray-800">Review Risiko</h4>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <x-risk-level-badge :level="$riskReview['risk_level'] ?? null" />
                <x-review-status-badge :status="$riskReview['review_status'] ?? null" />
                @if (!empty($riskReview['reviewed_at']) && !empty($riskReview['reviewed_by_name']))
                    <span class="text-xs text-gray-500">
                        Ditinjau oleh <span class="font-semibold text-gray-700">{{ $riskReview['reviewed_by_name'] }}</span>
                        pada {{ $riskReview['reviewed_at']->timezone('Asia/Jakarta')->format('d/m/Y H:i') }}
                    </span>
                @endif
            </div>
            <p class="max-w-3xl text-sm text-gray-600">{{ $riskReview['summary_text'] ?? 'Belum ada hasil analisis risiko untuk transaksi ini.' }}</p>
            <div class="grid grid-cols-1 gap-3 pt-1 sm:grid-cols-3">
                <x-ui-stat-card title="Alasan" :value="count($riskReview['reasons'] ?? [])" description="Indikasi yang tercatat." class="!rounded-xl !px-3 !py-3" />
                <x-ui-stat-card title="Kandidat Mirip" :value="count($riskReview['duplicate_candidates'] ?? [])" description="Transaksi pembanding." class="!rounded-xl !px-3 !py-3" />
                <x-ui-stat-card title="Status Operator" :value="match($riskReview['review_status'] ?? null) { 'perlu_tindak_lanjut' => 'Perlu Tindak Lanjut', 'aman' => 'Aman', default => 'Belum Ditinjau', }" description="Keputusan review terakhir." class="!rounded-xl !px-3 !py-3 [&_.ui-stat-value]:!text-sm [&_.ui-stat-value]:!leading-tight" />
            </div>
        </div>

        <form method="POST" action="{{ route('internal.transactions.risk_review_status', ['transaction' => $mainTx->id]) }}" class="w-full lg:min-w-[260px] lg:w-auto">
            @csrf
            @method('PATCH')
            <label for="review_status" class="mb-2 block text-[11px] font-semibold text-gray-500">Status review operator</label>
            <div class="flex flex-col gap-2 sm:flex-row lg:flex-col">
                <select id="review_status" name="review_status" class="ui-select w-full bg-white">
                    @foreach ($reviewStatuses as $statusValue)
                        <option value="{{ $statusValue }}" @selected(($riskReview['review_status'] ?? null) === $statusValue)>
                            {{ str_replace('_', ' ', ucfirst($statusValue)) }}
                        </option>
                    @endforeach
                </select>
                <button type="submit" class="ui-btn ui-btn-primary">
                    Simpan Status Review
                </button>
            </div>
        </form>
    </div>

    <div class="mt-5 grid grid-cols-1 gap-4 lg:grid-cols-2">
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
                <p class="text-sm text-gray-500">Belum ada alasan risiko yang tercatat untuk transaksi ini.</p>
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
</div>
