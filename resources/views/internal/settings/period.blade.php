<x-app-layout>
    <x-slot name="header">
        <div class="space-y-1 text-center sm:text-left">
            <h2 class="ui-page-title">
                <svg xmlns="http://www.w3.org/2000/svg" class="ui-page-title-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                Pengaturan Periode
            </h2>
            <p class="ui-page-title-copy">Atur periode pencatatan, nominal default, dan sumber grafik yang tampil di dashboard.</p>
        </div>
    </x-slot>

    <div class="py-4 sm:py-6">
        <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="ui-alert ui-alert-success">
                    <svg xmlns="http://www.w3.org/2000/svg" class="ui-alert-icon text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span class="font-medium">{{ session('status') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div class="ui-alert ui-alert-error">
                    <div class="w-full">
                        <div class="ui-alert-title text-red-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="ui-alert-icon" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                            Periksa input:
                        </div>
                        <ul class="list-disc pl-10 space-y-1 text-sm">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <div class="ui-card overflow-hidden"
                x-data="{
                    tab: 'config',
                    dashboardMode: @js(old('dashboard_chart_mode', $dashboardChartMode)),
                    dashboardArchive: @js((bool) old('dashboard_chart_show_offseason_archive', $dashboardChartShowOffseasonArchive)),
                    dashboardAutoSwitch: @js((bool) old('dashboard_chart_auto_switch_on_new_active_period', $dashboardChartAutoSwitchOnNewActivePeriod)),
                    periodStartsAt: @js(old('period_starts_at', optional($activePeriod?->starts_at)->toDateString())),
                    formatGregorianMonth(value) {
                        if (!value) return 'Bulan Masehi akan terbaca otomatis setelah tanggal mulai diisi.';
                        const date = new Date(value + 'T00:00:00');
                        if (Number.isNaN(date.getTime())) return 'Tanggal mulai belum valid.';
                        return new Intl.DateTimeFormat('id-ID', { month: 'long', year: 'numeric' }).format(date);
                    },
                    year: @js((string) old('new_year', $activeYear + 1)),
                    confirmYear: @js((string) old('new_year_confirmation')),
                    backup: @js((bool) old('backup_confirmed')),
                }">

                <div class="ui-card-header ui-card-header-slate flex-wrap justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="ui-card-header-icon text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <h3 class="ui-card-header-title">Konfigurasi Periode Zakat</h3>
                    </div>
                    <div class="flex rounded-lg border border-slate-200 bg-slate-100/60 p-0.5">
                        <button type="button" @click="tab = 'config'"
                            :class="tab === 'config' ? 'bg-white shadow-sm text-slate-900' : 'text-slate-500 hover:text-slate-700'"
                            class="rounded-md px-3 py-1.5 text-sm font-semibold transition">
                            Edit Periode Aktif
                        </button>
                        <button type="button" @click="tab = 'new_period'"
                            :class="tab === 'new_period' ? 'bg-amber-600 text-white shadow-sm' : 'text-slate-500 hover:text-amber-700'"
                            class="rounded-md px-3 py-1.5 text-sm font-semibold transition">
                            Buka Tahun Baru
                        </button>
                    </div>
                </div>

                {{-- Tab: Konfigurasi --}}
                <div x-show="tab === 'config'" class="p-4 sm:p-5">
                    <div class="mb-4">
                        <div class="ui-settings-kicker text-brand-700">Status Saat Ini</div>
                        <div class="mt-3 grid grid-cols-1 gap-3 text-sm md:grid-cols-3">
                            <div class="ui-settings-summary-card ui-settings-summary-card-emerald">
                                <div class="text-[11px] font-bold text-brand-700">Periode Aktif</div>
                                <div class="mt-1 text-base font-bold text-brand-950">{{ $activePeriod?->display_label ?? $activeYear }}</div>
                                <div class="mt-1 text-xs leading-5 text-brand-800">
                                    Dipakai sebagai default saat panitia mencatat transaksi baru.
                                </div>
                            </div>
                            <div class="ui-settings-summary-card ui-settings-summary-card-emerald">
                                <div class="text-[11px] font-bold text-brand-700">Rentang Grafik Periode</div>
                                <div class="mt-1 text-base font-bold text-brand-950">{{ $chartRange['label'] ?? '-' }}</div>
                                <div class="mt-1 text-xs leading-5 text-brand-800">
                                    Mengikuti tanggal grafik pada periode aktif.
                                </div>
                            </div>
                            <div class="ui-settings-summary-card ui-settings-summary-card-emerald">
                                <div class="text-[11px] font-bold text-brand-700">Grafik Dashboard Saat Ini</div>
                                <div class="mt-1 text-base font-bold text-brand-950">{{ $dashboardChartRange['period_label'] ?? 'Belum dipilih' }}</div>
                                <div class="mt-0.5 text-[11px] text-brand-700">{{ $dashboardChartRange['label'] ?? '-' }}</div>
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('internal.settings.period.update') }}" class="space-y-4">
                        @csrf

                        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                            <div class="space-y-4">
                                <section class="ui-settings-panel ui-settings-panel-muted">
                                    <div class="ui-settings-section-head">
                                        <span class="ui-section-accent"></span>
                                        <div>
                                            <h4 class="ui-settings-section-title">1. Dasar Sistem</h4>
                                            <p class="ui-settings-section-copy">Tahun aktif tidak diedit manual dari sini. Gunakan tab buka tahun baru untuk pergantian resmi.</p>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                        <div>
                                            <label class="ui-form-label" for="active_year">Tahun Aktif Sistem</label>
                                            <input id="active_year" name="active_year" type="number" min="2000" max="2100" value="{{ old('active_year', $activeYear) }}" class="ui-input w-full bg-slate-50" readonly required />
                                            <x-input-error class="mt-2" :messages="$errors->get('active_year')" />
                                        </div>
                                        <div>
                                            <label class="ui-form-label" for="public_refresh_interval_seconds">Refresh Halaman Publik (detik)</label>
                                            <input id="public_refresh_interval_seconds" name="public_refresh_interval_seconds" type="number" min="0" max="60" value="{{ old('public_refresh_interval_seconds', $publicRefreshIntervalSeconds ?? 15) }}" class="ui-input w-full" required />
                                            <p class="mt-1 text-xs leading-5 text-slate-500">0 berarti data publik tidak refresh otomatis.</p>
                                            <x-input-error class="mt-2" :messages="$errors->get('public_refresh_interval_seconds')" />
                                        </div>
                                    </div>
                                </section>

                                <section class="ui-settings-panel ui-settings-panel-muted">
                                    <div class="ui-settings-section-head">
                                        <span class="ui-section-accent"></span>
                                        <div>
                                            <h4 class="ui-settings-section-title">2. Identitas Periode Aktif</h4>
                                            <p class="ui-settings-section-copy">Bagian ini hanya mengubah nama dan rentang periode yang sedang aktif.</p>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                        <div class="sm:col-span-2">
                                            <label class="ui-form-label" for="period_label">Nama Periode</label>
                                            <input id="period_label" name="period_label" type="text" maxlength="80" value="{{ old('period_label', $activePeriod?->label ?? ('Ramadan ' . $activeYear)) }}" class="ui-input w-full" />
                                            <p class="mt-1 text-xs leading-5 text-slate-500">Contoh: Ramadan {{ $activeYear }} atau Zakat Fitrah {{ $activeYear }}.</p>
                                            <x-input-error class="mt-2" :messages="$errors->get('period_label')" />
                                        </div>
                                        <div>
                                            <label class="ui-form-label" for="hijri_year">Tahun Hijriah</label>
                                            <input id="hijri_year" name="hijri_year" type="number" min="1300" max="1600" value="{{ old('hijri_year', $activePeriod?->hijri_year) }}" class="ui-input w-full" placeholder="Contoh: 1451" />
                                            <x-input-error class="mt-2" :messages="$errors->get('hijri_year')" />
                                        </div>
                                        <div>
                                            <label class="ui-form-label" for="period_starts_at">Tanggal Mulai Penerimaan</label>
                                            <input id="period_starts_at" name="period_starts_at" type="date" x-model="periodStartsAt" value="{{ old('period_starts_at', optional($activePeriod?->starts_at)->toDateString()) }}" class="ui-input w-full" />
                                            <x-input-error class="mt-2" :messages="$errors->get('period_starts_at')" />
                                        </div>
                                        <div>
                                            <label class="ui-form-label" for="period_ends_at">Tanggal Selesai Penerimaan</label>
                                            <input id="period_ends_at" name="period_ends_at" type="date" value="{{ old('period_ends_at', optional($activePeriod?->ends_at)->toDateString()) }}" class="ui-input w-full" />
                                            <x-input-error class="mt-2" :messages="$errors->get('period_ends_at')" />
                                        </div>
                                    </div>
                                </section>

                                <section class="ui-settings-panel ui-settings-panel-muted">
                                    <div class="ui-settings-section-head">
                                        <span class="ui-section-accent"></span>
                                        <div>
                                            <h4 class="ui-settings-section-title">3. Nominal Default Transaksi Baru</h4>
                                            <p class="ui-settings-section-copy">Nilai ini menjadi isian awal saat input transaksi. Transaksi lama tidak dihitung ulang otomatis.</p>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                        <div>
                                            <label class="ui-form-label" for="default_fitrah_cash_per_jiwa">Nominal Fitrah Uang / Jiwa (Rp)</label>
                                            <input id="default_fitrah_cash_per_jiwa" name="default_fitrah_cash_per_jiwa" type="number" min="0" value="{{ old('default_fitrah_cash_per_jiwa', $defaultFitrahCashPerJiwa) }}" class="ui-input w-full" required />
                                            <x-input-error class="mt-2" :messages="$errors->get('default_fitrah_cash_per_jiwa')" />
                                        </div>
                                        <div>
                                            <label class="ui-form-label" for="default_fitrah_beras_per_jiwa">Takaran Fitrah Beras / Jiwa (Kg)</label>
                                            <input id="default_fitrah_beras_per_jiwa" name="default_fitrah_beras_per_jiwa" type="number" step="0.01" min="0" value="{{ old('default_fitrah_beras_per_jiwa', $defaultFitrahBerasPerJiwa) }}" class="ui-input w-full" required />
                                            <x-input-error class="mt-2" :messages="$errors->get('default_fitrah_beras_per_jiwa')" />
                                        </div>
                                        <div>
                                            <label class="ui-form-label" for="default_fidyah_per_hari">Nominal Fidyah / Hari (Rp)</label>
                                            <input id="default_fidyah_per_hari" name="default_fidyah_per_hari" type="number" min="0" value="{{ old('default_fidyah_per_hari', $defaultFidyahPerHari) }}" class="ui-input w-full" required />
                                            <x-input-error class="mt-2" :messages="$errors->get('default_fidyah_per_hari')" />
                                        </div>
                                        <div>
                                            <label class="ui-form-label" for="default_fidyah_beras_per_hari">Takaran Fidyah Beras / Hari (Kg)</label>
                                            <input id="default_fidyah_beras_per_hari" name="default_fidyah_beras_per_hari" type="number" step="0.01" min="0" value="{{ old('default_fidyah_beras_per_hari', $defaultFidyahBerasPerHari) }}" class="ui-input w-full" required />
                                            <x-input-error class="mt-2" :messages="$errors->get('default_fidyah_beras_per_hari')" />
                                        </div>
                                    </div>
                                </section>
                            </div>

                            <div class="space-y-4">
                                <div class="ui-settings-panel ui-settings-panel-emerald">
                                    <div class="ui-settings-section-head">
                                        <span class="ui-section-accent"></span>
                                        <div>
                                            <h4 class="ui-settings-section-title">4. Rentang Grafik Periode Aktif</h4>
                                            <p class="ui-settings-section-copy">Atur tanggal yang dipakai untuk membaca grafik periode aktif. Kosongkan tanggal jika ingin mengikuti rentang periode.</p>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                        <div>
                                            <label class="ui-form-label" for="chart_starts_at">Tanggal Awal Grafik</label>
                                            <input id="chart_starts_at" name="chart_starts_at" type="date" value="{{ old('chart_starts_at', $chartStartsAt) }}" class="ui-input w-full" />
                                            <x-input-error class="mt-2" :messages="$errors->get('chart_starts_at')" />
                                        </div>
                                        <div>
                                            <label class="ui-form-label" for="chart_ends_at">Tanggal Akhir Grafik</label>
                                            <input id="chart_ends_at" name="chart_ends_at" type="date" value="{{ old('chart_ends_at', $chartEndsAt) }}" class="ui-input w-full" />
                                            <x-input-error class="mt-2" :messages="$errors->get('chart_ends_at')" />
                                        </div>
                                        <div>
                                            <label class="ui-form-label" for="chart_fallback_buffer_days">Tambahan Hari Setelah Periode</label>
                                            <input id="chart_fallback_buffer_days" name="chart_fallback_buffer_days" type="number" min="0" max="14" value="{{ old('chart_fallback_buffer_days', $chartFallbackBufferDays) }}" class="ui-input w-full" required />
                                            <p class="mt-1 text-xs leading-5 text-slate-500">Dipakai agar grafik tidak langsung berhenti tepat di tanggal selesai.</p>
                                            <x-input-error class="mt-2" :messages="$errors->get('chart_fallback_buffer_days')" />
                                        </div>
                                    </div>
                                </div>

                                <div class="ui-settings-panel ui-settings-panel-emerald">
                                    <div class="ui-settings-section-head">
                                        <span class="ui-section-accent"></span>
                                        <div>
                                            <h4 class="ui-settings-section-title">5. Sumber Grafik Dashboard</h4>
                                            <p class="ui-settings-section-copy">Pilih apakah dashboard mengikuti periode aktif, periode tertentu, atau arsip terakhir.</p>
                                        </div>
                                    </div>
                                    <div class="ui-settings-summary-card ui-settings-summary-card-emerald">
                                        <div class="ui-settings-kicker text-brand-700">Tampil Sekarang</div>
                                        <div class="mt-2 text-base font-bold text-slate-950">{{ $dashboardChartRange['period_label'] ?? 'Belum ada periode yang dipilih' }}</div>
                                        <div class="mt-1 text-sm text-slate-600">{{ $dashboardChartRange['label'] ?? 'Range akan mengikuti pengaturan yang Anda simpan.' }}</div>
                                        @if (!empty($dashboardChartRange['fallback_note']))
                                            <div class="mt-3 rounded-xl border border-amber-100 bg-amber-50 px-3 py-2 text-xs text-amber-800">
                                                {{ $dashboardChartRange['fallback_note'] }}
                                            </div>
                                        @endif
                                    </div>

                                    <div class="mt-4">
                                        <div class="ui-form-label mb-2">Mode Grafik Dashboard</div>
                                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                                            @php
                                                $dashboardChartModeDescriptions = [
                                                    \App\Services\Charts\ChartRangeResolver::DASHBOARD_MODE_ACTIVE_PERIOD => 'Paling aman untuk operasional harian. Dashboard selalu ikut periode aktif.',
                                                    \App\Services\Charts\ChartRangeResolver::DASHBOARD_MODE_MANUAL_PERIOD => 'Kunci dashboard ke periode tertentu untuk review atau laporan arsip.',
                                                    \App\Services\Charts\ChartRangeResolver::DASHBOARD_MODE_LAST_COMPLETED_PERIOD => 'Tampilkan periode terakhir yang sudah selesai saat sedang di luar musim zakat.',
                                                ];
                                            @endphp
                                            @foreach ($dashboardChartModes as $modeValue => $modeLabel)
                                                <label class="ui-settings-choice"
                                                    :class="dashboardMode === '{{ $modeValue }}'
                                                        ? 'border-brand-300 ring-2 ring-brand-200'
                                                        : 'border-slate-200 hover:border-brand-200'">
                                                    <input type="radio" name="dashboard_chart_mode" value="{{ $modeValue }}" x-model="dashboardMode" class="sr-only" />
                                                    <span class="block font-bold text-slate-900">{{ $modeLabel }}</span>
                                                    <span class="ui-settings-choice-copy">{{ $dashboardChartModeDescriptions[$modeValue] ?? 'Atur sumber grafik dashboard.' }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                        <x-input-error class="mt-2" :messages="$errors->get('dashboard_chart_mode')" />
                                    </div>

                                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                                        <div x-show="dashboardMode === '{{ \App\Services\Charts\ChartRangeResolver::DASHBOARD_MODE_MANUAL_PERIOD }}'" x-cloak>
                                            <label class="ui-form-label" for="dashboard_chart_period_id">Periode Manual Dashboard</label>
                                            <select id="dashboard_chart_period_id" name="dashboard_chart_period_id" class="ui-select w-full">
                                                <option value="">Pilih periode</option>
                                                @foreach ($dashboardChartPeriods as $periodOption)
                                                    <option value="{{ $periodOption->id }}" @selected((string) old('dashboard_chart_period_id', $dashboardChartPeriodId) === (string) $periodOption->id)>
                                                        {{ $periodOption->display_label }}{{ $periodOption->sequence > 1 ? ' #' . $periodOption->sequence : '' }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <x-input-error class="mt-2" :messages="$errors->get('dashboard_chart_period_id')" />
                                        </div>
                                        <div class="ui-settings-note border-brand-100 text-brand-800 sm:col-span-2">
                                            <span x-show="dashboardMode === '{{ \App\Services\Charts\ChartRangeResolver::DASHBOARD_MODE_ACTIVE_PERIOD }}'">
                                                Dashboard mengikuti periode aktif.
                                            </span>
                                            <span x-show="dashboardMode === '{{ \App\Services\Charts\ChartRangeResolver::DASHBOARD_MODE_MANUAL_PERIOD }}'" x-cloak>
                                                Dashboard memakai periode pilihan sampai diubah lagi.
                                            </span>
                                            <span x-show="dashboardMode === '{{ \App\Services\Charts\ChartRangeResolver::DASHBOARD_MODE_LAST_COMPLETED_PERIOD }}'" x-cloak>
                                                Dashboard memakai periode terakhir yang selesai.
                                            </span>
                                        </div>
                                        <div>
                                            <label class="ui-form-label" for="dashboard_chart_starts_at">Override Tanggal Awal</label>
                                            <input id="dashboard_chart_starts_at" name="dashboard_chart_starts_at" type="date" value="{{ old('dashboard_chart_starts_at', $dashboardChartStartsAt) }}" class="ui-input w-full" />
                                            <x-input-error class="mt-2" :messages="$errors->get('dashboard_chart_starts_at')" />
                                        </div>
                                        <div>
                                            <label class="ui-form-label" for="dashboard_chart_ends_at">Override Tanggal Akhir</label>
                                            <input id="dashboard_chart_ends_at" name="dashboard_chart_ends_at" type="date" value="{{ old('dashboard_chart_ends_at', $dashboardChartEndsAt) }}" class="ui-input w-full" />
                                            <x-input-error class="mt-2" :messages="$errors->get('dashboard_chart_ends_at')" />
                                        </div>
                                    </div>
                                    <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                        <label class="ui-settings-check border-brand-100 text-brand-900">
                                            <input type="checkbox" name="dashboard_chart_show_offseason_archive" value="1" x-model="dashboardArchive" @checked(old('dashboard_chart_show_offseason_archive', $dashboardChartShowOffseasonArchive)) class="mt-0.5 rounded border-slate-300 text-brand-600 shadow-sm focus:ring-brand-500" />
                                            <span>
                                                <span class="block font-bold">Tetap tampilkan arsip saat belum musim zakat</span>
                                            </span>
                                        </label>
                                        <label class="ui-settings-check border-brand-100 text-brand-900">
                                            <input type="checkbox" name="dashboard_chart_auto_switch_on_new_active_period" value="1" x-model="dashboardAutoSwitch" @checked(old('dashboard_chart_auto_switch_on_new_active_period', $dashboardChartAutoSwitchOnNewActivePeriod)) class="mt-0.5 rounded border-slate-300 text-brand-600 shadow-sm focus:ring-brand-500" />
                                            <span>
                                                <span class="block font-bold">Ganti otomatis ke periode aktif baru</span>
                                            </span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="ui-settings-panel ui-settings-panel-muted sm:flex sm:items-center sm:justify-between">
                            <div class="mb-4 sm:mb-0">
                                <div class="text-sm font-bold text-slate-900">Simpan perubahan periode</div>
                                <p class="mt-1 text-xs leading-5 text-slate-500">Menyimpan pengaturan di tab ini saja. Membuka tahun baru ada di tab terpisah.</p>
                            </div>
                            <div class="flex flex-col items-stretch gap-3 sm:flex-row sm:items-center">
                                <a href="{{ route('dashboard') }}" class="ui-btn ui-btn-secondary w-full sm:w-auto">Kembali</a>
                                <button type="submit" class="ui-btn ui-btn-primary w-full px-6 py-3 sm:w-auto">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                                    </svg>
                                    Simpan Pengaturan
                                </button>
                            </div>
                        </div>

                        <p class="text-center text-xs text-slate-400 sm:text-left">
                            Tahun yang tersedia saat ini: {{ implode(', ', $years) }}
                        </p>
                    </form>
                </div>

                {{-- Tab: Mulai Periode Baru --}}
                <div x-show="tab === 'new_period'" x-cloak class="p-4 sm:p-5">
                    <div class="ui-settings-panel ui-settings-panel-amber p-3 text-sm text-amber-900 sm:p-4">
                        <p class="font-bold">Gunakan hanya saat benar-benar masuk periode/tahun zakat baru.</p>
                        <p class="mt-1 text-xs leading-5 text-amber-700">Aksi ini membuat periode aktif baru dan mengubah default sistem. Backup database sebelum lanjut.</p>
                    </div>

                    <form method="POST" action="{{ route('internal.settings.period.start_new') }}" class="mt-4 space-y-4">
                        @csrf

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="ui-form-label" for="new_year">Tahun Aktif Baru</label>
                                <input id="new_year" name="new_year" type="number" min="2000" max="2100" x-model="year" class="ui-input w-full" required />
                                <x-input-error class="mt-2" :messages="$errors->get('new_year')" />
                            </div>

                            <div>
                                <label class="ui-form-label" for="new_year_confirmation">Konfirmasi Tahun Baru</label>
                                <input id="new_year_confirmation" name="new_year_confirmation" type="text" inputmode="numeric" x-model="confirmYear" class="ui-input w-full focus:border-red-500 focus:ring-red-500" placeholder="Contoh: {{ $activeYear + 1 }}" required />
                                <x-input-error class="mt-2" :messages="$errors->get('new_year_confirmation')" />
                            </div>
                        </div>

                        <label class="ui-settings-check rounded-xl border-amber-100 bg-amber-50 text-sm">
                            <input type="checkbox" name="backup_confirmed" value="1" x-model="backup" class="mt-0.5 rounded border-slate-300 text-amber-600 shadow-sm focus:ring-amber-500" required />
                            <span class="text-amber-900">Saya sudah backup database atau siap melanjutkan perubahan periode aktif ini.</span>
                        </label>
                        <x-input-error class="mt-2" :messages="$errors->get('backup_confirmed')" />

                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
                            <button type="submit" :disabled="String(year) !== String(confirmYear) || !backup" class="ui-btn w-full bg-amber-600 px-5 py-3 text-white shadow-sm hover:bg-amber-700 focus:ring-amber-500 sm:w-auto">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                                Mulai Periode Baru
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
