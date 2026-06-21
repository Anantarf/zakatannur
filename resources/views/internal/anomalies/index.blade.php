<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-1 text-center sm:text-left">
                <h2 class="ui-page-title">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 shrink-0 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z" />
                    </svg>
                    Review Anomali
                </h2>
                <p class="ui-body-muted">Antrean admin untuk memeriksa warning transaksi, menutup kasus yang aman, dan menandai kasus yang perlu tindak lanjut.</p>
            </div>
            <a href="{{ route('internal.transactions.index') }}" class="ui-btn ui-btn-secondary w-full sm:w-auto">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Kembali ke Riwayat
            </a>
        </div>
    </x-slot>

    <div class="py-5 sm:py-8">
        <div class="mx-auto max-w-7xl space-y-4 sm:space-y-5 px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-5">
                <a href="{{ route('internal.anomalies.index', ['scope' => $scope ?? 'active']) }}" class="block rounded-card transition hover:ring-2 hover:ring-brand-200 focus:outline-none focus:ring-2 focus:ring-brand-500">
                    <x-ui-stat-card title="Total Kasus" :value="$overview['totalGroups']" description="Lihat semua kasus." />
                </a>
                <a href="{{ route('internal.anomalies.index', ['scope' => $scope ?? 'active', 'risk_level' => \App\Models\TransactionRiskReview::LEVEL_WARNING]) }}" class="block rounded-card transition hover:ring-2 hover:ring-amber-200 focus:outline-none focus:ring-2 focus:ring-amber-500">
                    <x-ui-stat-card title="Warning" :value="$overview['warningGroups']" description="Filter kasus warning." tone="warning" />
                </a>
                <a href="{{ route('internal.anomalies.index', ['scope' => 'active', 'review_status' => \App\Models\TransactionRiskReview::REVIEW_BELUM_DITINJAU]) }}" class="block rounded-card transition hover:ring-2 hover:ring-slate-200 focus:outline-none focus:ring-2 focus:ring-slate-500">
                    <x-ui-stat-card title="Belum Review" :value="$overview['pendingReviewGroups']" description="Filter antrean review." tone="muted" />
                </a>
                <a href="{{ route('internal.anomalies.index', ['scope' => 'archived', 'review_status' => \App\Models\TransactionRiskReview::REVIEW_AMAN]) }}" class="block rounded-card transition hover:ring-2 hover:ring-blue-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <x-ui-stat-card title="Aman" :value="$overview['safeReviewGroups']" description="Buka arsip aman." tone="info" />
                </a>
                <a href="{{ route('internal.anomalies.index', ['scope' => 'active', 'review_status' => \App\Models\TransactionRiskReview::REVIEW_PERLU_TINDAK_LANJUT]) }}" class="block rounded-card transition hover:ring-2 hover:ring-red-200 focus:outline-none focus:ring-2 focus:ring-red-500">
                    <x-ui-stat-card title="Tindak Lanjut" :value="$overview['followUpGroups']" description="Filter kasus lanjutan." tone="danger" />
                </a>
            </div>

            <div class="ui-card overflow-hidden shadow-md">
                <div class="border-b border-slate-100 px-4 py-3 sm:px-5">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex flex-wrap gap-2">
                            <a
                                href="{{ route('internal.anomalies.index', array_filter(['scope' => 'active'])) }}"
                                class="inline-flex items-center rounded-full border px-4 py-2 text-sm font-semibold transition {{ ($scope ?? 'active') === 'active' ? 'border-brand-600 bg-brand-600 text-white shadow-sm' : 'border-slate-200 bg-white text-slate-600 hover:border-brand-200 hover:text-brand-700' }}"
                                aria-current="{{ ($scope ?? 'active') === 'active' ? 'page' : 'false' }}"
                            >
                                Kasus Aktif
                            </a>
                            <a
                                href="{{ route('internal.anomalies.index', array_filter(['scope' => 'archived'])) }}"
                                class="inline-flex items-center rounded-full border px-4 py-2 text-sm font-semibold transition {{ ($scope ?? 'active') === 'archived' ? 'border-brand-600 bg-brand-600 text-white shadow-sm' : 'border-slate-200 bg-white text-slate-600 hover:border-brand-200 hover:text-brand-700' }}"
                                aria-current="{{ ($scope ?? 'active') === 'archived' ? 'page' : 'false' }}"
                            >
                                Riwayat Review
                            </a>
                        </div>

                        <p class="text-sm text-slate-500">
                            {{ ($scope ?? 'active') === 'archived'
                                ? 'Kasus yang sudah ditutup aman dipindahkan ke arsip review agar antrean aktif tetap ringkas.'
                                : 'Kasus aktif hanya menampilkan warning yang masih perlu perhatian admin.' }}
                        </p>
                    </div>
                </div>

                <div class="ui-toolbar-soft lg:flex-col xl:flex-row xl:items-start">
                    <div class="max-w-full space-y-1 xl:max-w-[280px] xl:flex-none">
                        <div class="ui-section-title">
                            <div class="ui-section-accent"></div>
                            <h3 class="font-semibold text-slate-800">{{ ($scope ?? 'active') === 'archived' ? 'Riwayat Review' : 'Daftar Kasus Aktif' }}</h3>
                        </div>
                        <p class="ui-body-muted">
                            {{ ($scope ?? 'active') === 'archived'
                                ? 'Lihat kembali kasus yang sudah selesai tanpa mengganggu antrean kerja aktif.'
                                : 'Utamakan kasus belum ditinjau, buka detail, lalu putuskan apakah aman atau perlu tindak lanjut.' }}
                        </p>
                    </div>

                    <form method="GET" action="{{ route('internal.anomalies.index') }}" class="flex w-full flex-col items-stretch gap-2 sm:flex-row sm:flex-wrap sm:items-center xl:justify-end">
                        <input type="hidden" name="scope" value="{{ $scope ?? 'active' }}" />
                        <input type="text" name="q" value="{{ $q ?? '' }}" placeholder="Cari nomor transaksi atau nama..." class="ui-input w-full sm:min-w-[240px] sm:flex-[1_1_260px] xl:max-w-[300px]" />

                        <div class="relative w-full sm:min-w-[140px] sm:flex-[0.8_1_140px] xl:max-w-[150px]">
                            <select name="year" class="ui-select w-full appearance-none pr-10">
                                <option value="">Semua Tahun</option>
                                @foreach ($years ?? [] as $y)
                                    <option value="{{ $y }}" @selected((string) ($year ?? '') === (string) $y)>{{ $y }}</option>
                                @endforeach
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-slate-400">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </div>
                        </div>

                        <div class="relative w-full sm:min-w-[210px] sm:flex-[1_1_210px] xl:max-w-[240px]">
                            <select name="period_id" class="ui-select w-full appearance-none pr-10">
                                <option value="">Semua Periode</option>
                                @foreach ($periods ?? [] as $period)
                                    <option value="{{ $period->id }}" @selected((string) ($periodId ?? '') === (string) $period->id)>
                                        {{ $period->display_label }}{{ $period->sequence > 1 ? ' #' . $period->sequence : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-slate-400">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </div>
                        </div>

                        <div class="relative w-full sm:min-w-[160px] sm:flex-[1_1_160px] xl:max-w-[180px]">
                            <select name="flag_type" class="ui-select w-full appearance-none pr-10">
                                <option value="">Semua Flag</option>
                                @foreach ($flagOptions ?? [] as $flagValue => $flagLabel)
                                    <option value="{{ $flagValue }}" @selected(($flag_type ?? '') === $flagValue)>{{ $flagLabel }}</option>
                                @endforeach
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-slate-400">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </div>
                        </div>

                        <div class="relative w-full sm:min-w-[150px] sm:flex-[1_1_150px] xl:max-w-[170px]">
                            <select name="risk_level" class="ui-select w-full appearance-none pr-10">
                                <option value="">Semua Level</option>
                                @foreach ($riskLevels ?? [] as $level)
                                    <option value="{{ $level }}" @selected(($risk_level ?? '') === $level)>{{ \App\Models\TransactionRiskReview::levelLabel($level) }}</option>
                                @endforeach
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-slate-400">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </div>
                        </div>

                        <div class="relative w-full sm:min-w-[170px] sm:flex-[1_1_170px] xl:max-w-[190px]">
                            <select name="review_status" class="ui-select w-full appearance-none pr-10">
                                <option value="">Semua Review</option>
                                @foreach ($reviewStatuses ?? [] as $statusValue)
                                    <option value="{{ $statusValue }}" @selected(($review_status ?? '') === $statusValue)>{{ \App\Models\TransactionRiskReview::reviewStatusLabel($statusValue) }}</option>
                                @endforeach
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-slate-400">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </div>
                        </div>

                        <button type="submit" class="ui-btn ui-btn-secondary w-full sm:w-auto sm:flex-none">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            Terapkan
                        </button>

                        @if (($q ?? null) || ($year ?? null) || ($periodId ?? null) || ($flag_type ?? null) || ($risk_level ?? null) || ($review_status ?? null))
                            <a href="{{ route('internal.anomalies.index', ['scope' => $scope ?? 'active']) }}" class="ui-btn ui-btn-secondary w-full sm:w-auto sm:flex-none">Reset</a>
                        @endif
                    </form>
                </div>

                @if (($scope ?? 'active') === 'active')
                    <div class="border-t border-slate-100 bg-amber-50/60 px-4 py-3 text-sm leading-6 text-amber-900 sm:px-5">
                        Warning bukan berarti transaksi salah. Halaman ini dipakai untuk memeriksa sinyal sistem, lalu memutuskan apakah kasus cukup ditutup aman atau perlu tindak lanjut.
                    </div>
                @endif

                <div class="space-y-3 p-3 md:hidden">
                    @forelse ($groups as $group)
                        @include('internal.anomalies.partials._mobile_card', ['group' => $group])
                    @empty
                        @include('internal.anomalies.partials._empty_state')
                    @endforelse
                </div>

                <div class="hidden overflow-x-auto md:block">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-100 bg-slate-50 text-left text-[11px] font-semibold uppercase tracking-[0.08em] text-slate-400 sm:text-xs">
                                <th class="px-3 py-3 sm:px-5">No. Transaksi</th>
                                <th class="px-3 py-3 sm:px-5">Waktu</th>
                                <th class="px-3 py-3 sm:px-5">Pembayar</th>
                                <th class="px-3 py-3 text-center">Kategori</th>
                                <th class="px-3 py-3 text-center">Risiko</th>
                                <th class="px-3 py-3 sm:px-5">Flag Utama</th>
                                <th class="px-3 py-3 text-center">Review</th>
                                <th class="px-3 py-3 text-center">Petugas</th>
                                <th class="px-3 py-3 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100/80">
                            @forelse ($groups as $group)
                                @include('internal.anomalies.partials._desktop_row', ['group' => $group])
                            @empty
                                <tr>
                                    <td colspan="9" class="px-6 py-12 text-center">
                                        @include('internal.anomalies.partials._empty_state', ['showBackToFirstPage' => true])
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($groups->hasPages())
                    <div class="border-t border-slate-100 px-5 py-3">
                        {{ $groups->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
