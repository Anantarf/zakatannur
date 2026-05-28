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
                <p class="text-sm text-slate-500">Antrean admin untuk memeriksa warning transaksi, menutup kasus yang aman, dan menandai kasus yang perlu tindak lanjut.</p>
            </div>
            <a href="{{ route('internal.transactions.index') }}" class="ui-btn ui-btn-secondary w-full sm:w-auto">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Kembali ke Riwayat
            </a>
        </div>
    </x-slot>

    <div class="py-6 sm:py-10">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-5">
                <x-ui-stat-card title="Total Kasus" :value="$overview['totalGroups']" description="Grup transaksi sesuai filter." />
                <x-ui-stat-card title="Warning" :value="$overview['warningGroups']" description="Kasus yang perlu dicek admin." tone="warning" />
                <x-ui-stat-card title="Belum Review" :value="$overview['pendingReviewGroups']" description="Belum diputuskan admin." tone="muted" />
                <x-ui-stat-card title="Aman" :value="$overview['safeReviewGroups']" description="Sudah ditutup aman." tone="info" />
                <x-ui-stat-card title="Tindak Lanjut" :value="$overview['followUpGroups']" description="Masih perlu aksi lanjutan." tone="danger" />
            </div>

            <div class="ui-card overflow-hidden shadow-md">
                <div class="border-b border-gray-100 px-4 py-4 sm:px-6">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex flex-wrap gap-2">
                            <a
                                href="{{ route('internal.anomalies.index', array_filter(['scope' => 'active'])) }}"
                                class="inline-flex items-center rounded-full border px-4 py-2 text-sm font-semibold transition {{ ($scope ?? 'active') === 'active' ? 'border-emerald-600 bg-emerald-600 text-white shadow-sm' : 'border-slate-200 bg-white text-slate-600 hover:border-emerald-200 hover:text-emerald-700' }}"
                            >
                                Kasus Aktif
                            </a>
                            <a
                                href="{{ route('internal.anomalies.index', array_filter(['scope' => 'archived'])) }}"
                                class="inline-flex items-center rounded-full border px-4 py-2 text-sm font-semibold transition {{ ($scope ?? 'active') === 'archived' ? 'border-sky-600 bg-sky-600 text-white shadow-sm' : 'border-slate-200 bg-white text-slate-600 hover:border-sky-200 hover:text-sky-700' }}"
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
                            <div class="h-6 w-2 rounded-full bg-amber-500"></div>
                            <h3 class="font-semibold text-gray-800">{{ ($scope ?? 'active') === 'archived' ? 'Riwayat Review' : 'Daftar Kasus Aktif' }}</h3>
                        </div>
                        <p class="text-sm leading-6 text-slate-500">
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
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-400">
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
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-400">
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
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-400">
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
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-400">
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
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-400">
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
                    <div class="border-t border-gray-100 bg-amber-50/60 px-4 py-3 text-sm leading-6 text-amber-900 sm:px-6">
                        Warning bukan berarti transaksi salah. Halaman ini dipakai untuk memeriksa sinyal sistem, lalu memutuskan apakah kasus cukup ditutup aman atau perlu tindak lanjut.
                    </div>
                @endif

                <div class="space-y-4 p-4 md:hidden">
                    @forelse ($groups as $group)
                        @php
                            $groupTime = ($group->waktu_terima ?? $group->created_at)?->timezone('Asia/Jakarta');
                        @endphp
                        <article class="ui-mobile-card border-amber-100">
                            <div class="flex items-start justify-between gap-3">
                                <div class="space-y-1">
                                    <span class="inline-flex rounded-md bg-blue-50 px-2 py-1 font-mono text-xs font-semibold text-blue-600">{{ $group->no_transaksi }}</span>
                                    <h4 class="text-sm font-bold leading-tight text-slate-900">{{ $group->pembayar_nama }}</h4>
                                    <p class="text-xs text-slate-500">
                                        {{ $groupTime?->format('d/m/Y H:i') ?? '-' }}
                                        @if($group->flags_count > 1)
                                            <span class="ml-1">+ {{ $group->flags_count - 1 }} flag lain</span>
                                        @endif
                                    </p>
                                </div>
                                <x-risk-level-badge :level="$group->risk_level" />
                            </div>

                            <div class="ui-mobile-meta-grid">
                                <div class="ui-mobile-meta-item col-span-2">
                                    <p class="ui-mobile-meta-label">Kategori</p>
                                    <div class="mt-1">
                                        <x-zakat-category-tags :categories="$group->categories_list" />
                                    </div>
                                </div>
                                <div class="ui-mobile-meta-item">
                                    <p class="ui-mobile-meta-label">Review</p>
                                    <x-review-status-badge :status="$group->review_status" />
                                </div>
                                <div class="ui-mobile-meta-item">
                                    <p class="ui-mobile-meta-label">Petugas</p>
                                    <div class="ui-mobile-meta-value">{{ $group->petugas?->name ?? '-' }}</div>
                                    <span class="mt-1 inline-flex items-center justify-center rounded px-2 py-0.5 text-[11px] font-bold uppercase bg-emerald-50 text-emerald-700 border border-emerald-100 whitespace-nowrap leading-tight text-center">
                                        {{ $group->shift_label }}
                                    </span>
                                </div>
                                <div class="ui-mobile-meta-item col-span-2">
                                    <p class="ui-mobile-meta-label">Flag Utama</p>
                                    <p class="mt-1 text-sm leading-6 text-slate-600">{{ $group->primary_flag_label ?? '-' }}</p>
                                </div>
                            </div>

                            <a href="{{ route('internal.anomalies.show', ['noTransaksi' => $group->no_transaksi]) }}" class="ui-btn ui-btn-secondary mt-4 w-full justify-center border-amber-200 text-amber-700 hover:bg-amber-50">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                Tinjau Kasus
                            </a>
                        </article>
                    @empty
                        <div class="ui-empty-state-box">
                            <div class="flex flex-col items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="mb-2 h-10 w-10 text-gray-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <span class="text-sm font-medium text-gray-400">
                                    {{ ($scope ?? 'active') === 'archived'
                                        ? 'Belum ada kasus aman di riwayat review untuk filter ini.'
                                        : 'Belum ada kasus anomali aktif untuk filter ini.' }}
                                </span>
                            </div>
                        </div>
                    @endforelse
                </div>

                <div class="hidden overflow-x-auto md:block">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-100 bg-gray-50 text-left text-[11px] font-bold uppercase tracking-widest text-gray-400 sm:text-xs">
                                <th class="px-3 py-4 sm:px-6">No. Transaksi</th>
                                <th class="px-3 py-4 sm:px-6">Waktu</th>
                                <th class="px-3 py-4 sm:px-6">Pembayar</th>
                                <th class="px-3 py-4 text-center">Kategori</th>
                                <th class="px-3 py-4 text-center">Risiko</th>
                                <th class="px-3 py-4 sm:px-6">Flag Utama</th>
                                <th class="px-3 py-4 text-center">Review</th>
                                <th class="px-3 py-4 text-center">Petugas</th>
                                <th class="px-3 py-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100/80">
                            @forelse ($groups as $group)
                                <tr class="transition-colors hover:bg-amber-50/30">
                                    <td class="whitespace-nowrap px-3 py-4 sm:px-6">
                                        <span class="rounded-md bg-blue-50 px-2 py-1 font-mono text-xs font-semibold text-blue-600">{{ $group->no_transaksi }}</span>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-[13px] text-gray-500 sm:px-6">
                                        @php $groupTime = ($group->waktu_terima ?? $group->created_at)?->timezone('Asia/Jakarta'); @endphp
                                        <div class="leading-tight">
                                            <div>{{ $groupTime?->format('d/m/Y') }}</div>
                                            <div class="mt-1 text-[12px] text-slate-400">{{ $groupTime?->format('H:i') }}</div>
                                        </div>
                                    </td>
                                    <td class="px-3 py-4 sm:px-6">
                                        <div class="max-w-[180px] break-words text-sm font-semibold leading-tight text-gray-700">{{ $group->pembayar_nama }}</div>
                                        @if($group->flags_count > 1)
                                            <div class="mt-1 text-[11px] text-gray-400">+ {{ $group->flags_count - 1 }} flag lain</div>
                                        @endif
                                    </td>
                                    <td class="px-3 py-4 text-center">
                                        <x-zakat-category-tags :categories="$group->categories_list" />
                                    </td>
                                    <td class="px-3 py-4 text-center whitespace-nowrap">
                                        <div class="flex flex-col items-center gap-1">
                                            <x-risk-level-badge :level="$group->risk_level" />
                                        </div>
                                    </td>
                                    <td class="px-3 py-4 sm:px-6">
                                        <div class="max-w-[220px] text-sm leading-5 text-gray-600">{{ $group->primary_flag_label ?? '-' }}</div>
                                    </td>
                                    <td class="px-3 py-4 text-center whitespace-nowrap">
                                        <x-review-status-badge :status="$group->review_status" />
                                    </td>
                                    <td class="px-3 py-4 text-center text-[13px] text-gray-500">
                                        <div class="flex flex-col items-center gap-1">
                                            <span class="font-medium text-gray-700">{{ $group->petugas?->name ?? '-' }}</span>
                                            <span class="inline-flex items-center justify-center rounded px-2 py-0.5 text-[11px] font-bold uppercase leading-tight text-center whitespace-nowrap border border-emerald-100 bg-emerald-50 text-emerald-700">
                                                {{ $group->shift_label }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-3 py-4 text-center whitespace-nowrap">
                                        <a href="{{ route('internal.anomalies.show', ['noTransaksi' => $group->no_transaksi]) }}" class="ui-icon-button ui-icon-button-amber px-2" title="Buka review" aria-label="Buka review">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            <span class="ui-table-action-label">Review</span>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="mb-2 h-10 w-10 text-gray-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            <span class="text-sm font-medium text-gray-400">
                                                {{ ($scope ?? 'active') === 'archived'
                                                    ? 'Belum ada kasus aman di riwayat review untuk filter ini.'
                                                    : 'Belum ada kasus anomali aktif untuk filter ini.' }}
                                            </span>
                                            @if ($groups->total() > 0 && request()->has('page') && request('page') > 1)
                                                <div class="mt-4">
                                                    <a href="{{ request()->fullUrlWithQuery(['page' => 1]) }}" class="ui-btn ui-btn-secondary">
                                                        Kembali ke Halaman 1
                                                    </a>
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($groups->hasPages())
                    <div class="border-t border-gray-50 px-6 py-4">
                        {{ $groups->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
