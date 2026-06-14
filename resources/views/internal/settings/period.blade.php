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
            <p class="ui-page-title-copy">Kelola periode aktif, acuan zakat, dan grafik dashboard.</p>
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

            {{-- Settings Form --}}
            <div class="ui-card overflow-hidden">
                <div class="ui-card-header ui-card-header-emerald px-5 py-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="ui-card-header-icon text-brand-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h3 class="text-base font-bold text-brand-900">Konfigurasi Periode Aktif</h3>
                </div>
                <div class="p-4 sm:p-5">
                    <div class="mb-4">
                        <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                            <div>
                                <div class="ui-settings-kicker text-brand-700">Ringkasan</div>
                            </div>
                        </div>
                        <div class="mt-3 grid grid-cols-1 gap-3 text-sm md:grid-cols-3">
                            <div class="ui-settings-summary-card ui-settings-summary-card-emerald">
                                <div class="text-[11px] font-bold text-brand-700">Periode Aktif</div>
                                <div class="mt-1 text-base font-bold text-brand-950">{{ $activePeriod?->display_label ?? $activeYear }}</div>
                            </div>
                            <div class="ui-settings-summary-card ui-settings-summary-card-emerald">
                                <div class="text-[11px] font-bold text-brand-700">Grafik Periode Aktif</div>
                                <div class="mt-1 text-base font-bold text-brand-950">{{ $chartRange['label'] ?? '-' }}</div>
                            </div>
                            <div class="ui-settings-summary-card ui-settings-summary-card-emerald">
                                <div class="text-[11px] font-bold text-brand-700">Grafik Dashboard</div>
                                <div class="mt-1 text-base font-bold text-brand-950">{{ $dashboardChartRange['period_label'] ?? 'Belum dipilih' }}</div>
                                <div class="mt-0.5 text-[11px] text-brand-700">{{ $dashboardChartRange['label'] ?? '-' }}</div>
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('internal.settings.period.update') }}" class="space-y-4"
                        x-data="{
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
                        }">
                        @csrf

                        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                            <div class="space-y-4">
                        <section class="ui-settings-panel ui-settings-panel-muted">
                            <div class="ui-settings-section-head">
                                <span class="h-5 w-1 rounded-full bg-slate-500"></span>
                                <div>
                                    <h4 class="ui-settings-section-title">Umum</h4>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="ui-form-label" for="active_year">Tahun Aktif</label>
                                    <input id="active_year" name="active_year" type="number" min="2000" max="2100" value="{{ old('active_year', $activeYear) }}" class="ui-input w-full bg-slate-50" readonly required />
                                    <x-input-error class="mt-2" :messages="$errors->get('active_year')" />
                                </div>
                                <div>
                                    <label class="ui-form-label" for="public_refresh_interval_seconds">Refresh Data Publik (detik)</label>
                                    <input id="public_refresh_interval_seconds" name="public_refresh_interval_seconds" type="number" min="0" max="60" value="{{ old('public_refresh_interval_seconds', $publicRefreshIntervalSeconds ?? 15) }}" class="ui-input w-full" required />
                                    <x-input-error class="mt-2" :messages="$errors->get('public_refresh_interval_seconds')" />
                                </div>
                            </div>
                        </section>

                        <section class="ui-settings-panel ui-settings-panel-white">
                            <div class="ui-settings-section-head">
                                <span class="h-5 w-1 rounded-full bg-sky-500"></span>
                                <div>
                                    <h4 class="ui-settings-section-title">Periode</h4>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div class="sm:col-span-2">
                                    <label class="ui-form-label" for="period_label">Nama Periode</label>
                                    <input id="period_label" name="period_label" type="text" maxlength="80" value="{{ old('period_label', $activePeriod?->label ?? ('Ramadan ' . $activeYear)) }}" class="ui-input w-full" />
                                    <x-input-error class="mt-2" :messages="$errors->get('period_label')" />
                                </div>
                                <div>
                                    <label class="ui-form-label" for="hijri_year">Tahun Hijriah</label>
                                    <input id="hijri_year" name="hijri_year" type="number" min="1300" max="1600" value="{{ old('hijri_year', $activePeriod?->hijri_year) }}" class="ui-input w-full" placeholder="Contoh: 1451" />
                                    <x-input-error class="mt-2" :messages="$errors->get('hijri_year')" />
                                </div>
                                <div>
                                    <label class="ui-form-label" for="period_starts_at">Mulai Periode</label>
                                    <input id="period_starts_at" name="period_starts_at" type="date" x-model="periodStartsAt" value="{{ old('period_starts_at', optional($activePeriod?->starts_at)->toDateString()) }}" class="ui-input w-full" />
                                    <x-input-error class="mt-2" :messages="$errors->get('period_starts_at')" />
                                </div>
                                <div>
                                    <label class="ui-form-label" for="period_ends_at">Selesai Periode</label>
                                    <input id="period_ends_at" name="period_ends_at" type="date" value="{{ old('period_ends_at', optional($activePeriod?->ends_at)->toDateString()) }}" class="ui-input w-full" />
                                    <x-input-error class="mt-2" :messages="$errors->get('period_ends_at')" />
                                </div>
                            </div>
                        </section>

                        <section class="ui-settings-panel ui-settings-panel-white">
                            <div class="ui-settings-section-head">
                                <span class="h-5 w-1 rounded-full bg-brand-500"></span>
                                <div>
                                    <h4 class="ui-settings-section-title">Default Zakat</h4>
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
                            <div class="mb-4">
                                <h4 class="ui-settings-section-title text-brand-900">Grafik Periode</h4>
                            </div>
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                <div>
                                    <label class="ui-form-label" for="chart_starts_at">Mulai Grafik</label>
                                    <input id="chart_starts_at" name="chart_starts_at" type="date" value="{{ old('chart_starts_at', $chartStartsAt) }}" class="ui-input w-full" />
                                    <x-input-error class="mt-2" :messages="$errors->get('chart_starts_at')" />
                                </div>
                                <div>
                                    <label class="ui-form-label" for="chart_ends_at">Selesai Grafik</label>
                                    <input id="chart_ends_at" name="chart_ends_at" type="date" value="{{ old('chart_ends_at', $chartEndsAt) }}" class="ui-input w-full" />
                                    <x-input-error class="mt-2" :messages="$errors->get('chart_ends_at')" />
                                </div>
                                <div>
                                    <label class="ui-form-label" for="chart_fallback_buffer_days">Tambahan Hari Otomatis</label>
                                    <input id="chart_fallback_buffer_days" name="chart_fallback_buffer_days" type="number" min="0" max="14" value="{{ old('chart_fallback_buffer_days', $chartFallbackBufferDays) }}" class="ui-input w-full" required />
                                    <x-input-error class="mt-2" :messages="$errors->get('chart_fallback_buffer_days')" />
                                </div>
                            </div>
                        </div>

                        <div class="ui-settings-panel ui-settings-panel-sky">
                            <div class="mb-4">
                                <h4 class="ui-settings-section-title text-sky-900">Grafik Dashboard</h4>
                            </div>
                            <div class="ui-settings-summary-card border-sky-100 bg-white/80">
                                <div class="ui-settings-kicker text-sky-700">Tampil Sekarang</div>
                                <div class="mt-2 text-base font-bold text-slate-950">{{ $dashboardChartRange['period_label'] ?? 'Belum ada periode yang dipilih' }}</div>
                                <div class="mt-1 text-sm text-slate-600">{{ $dashboardChartRange['label'] ?? 'Range akan mengikuti pengaturan yang Anda simpan.' }}</div>
                                @if (!empty($dashboardChartRange['fallback_note']))
                                    <div class="mt-3 rounded-xl border border-amber-100 bg-amber-50 px-3 py-2 text-xs text-amber-800">
                                        {{ $dashboardChartRange['fallback_note'] }}
                                    </div>
                                @endif
                            </div>

                            <div class="mt-4">
                                <div class="ui-form-label mb-2">Cara Dashboard Mengambil Grafik</div>
                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                                    @foreach ($dashboardChartModes as $modeValue => $modeLabel)
                                        <label class="ui-settings-choice"
                                            :class="dashboardMode === '{{ $modeValue }}'
                                                ? 'border-sky-300 ring-2 ring-sky-200'
                                                : 'border-sky-100 hover:border-sky-200'">
                                            <input type="radio" name="dashboard_chart_mode" value="{{ $modeValue }}" x-model="dashboardMode" class="sr-only" />
                                            <span class="block font-bold text-slate-900">{{ $modeLabel }}</span>
                                        </label>
                                    @endforeach
                                </div>
                                <x-input-error class="mt-2" :messages="$errors->get('dashboard_chart_mode')" />
                            </div>

                            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div x-show="dashboardMode === '{{ \App\Services\Charts\ChartRangeResolver::DASHBOARD_MODE_MANUAL_PERIOD }}'" x-cloak>
                                    <label class="ui-form-label" for="dashboard_chart_period_id">Pilih Periode</label>
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
                                <div class="ui-settings-note sm:col-span-2 border-sky-100 text-sky-800">
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
                                    <label class="ui-form-label" for="dashboard_chart_starts_at">Batasi Mulai Grafik</label>
                                    <input id="dashboard_chart_starts_at" name="dashboard_chart_starts_at" type="date" value="{{ old('dashboard_chart_starts_at', $dashboardChartStartsAt) }}" class="ui-input w-full" />
                                    <x-input-error class="mt-2" :messages="$errors->get('dashboard_chart_starts_at')" />
                                </div>
                                <div>
                                    <label class="ui-form-label" for="dashboard_chart_ends_at">Batasi Selesai Grafik</label>
                                    <input id="dashboard_chart_ends_at" name="dashboard_chart_ends_at" type="date" value="{{ old('dashboard_chart_ends_at', $dashboardChartEndsAt) }}" class="ui-input w-full" />
                                    <x-input-error class="mt-2" :messages="$errors->get('dashboard_chart_ends_at')" />
                                </div>
                            </div>
                            <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <label class="ui-settings-check border-sky-100 text-sky-900">
                                    <input type="checkbox" name="dashboard_chart_show_offseason_archive" value="1" x-model="dashboardArchive" @checked(old('dashboard_chart_show_offseason_archive', $dashboardChartShowOffseasonArchive)) class="mt-0.5 rounded border-slate-300 text-sky-600 shadow-sm focus:ring-sky-500" />
                                    <span>
                                        <span class="block font-bold">Tetap tampilkan arsip saat belum musim zakat</span>
                                    </span>
                                </label>
                                <label class="ui-settings-check border-sky-100 text-sky-900">
                                    <input type="checkbox" name="dashboard_chart_auto_switch_on_new_active_period" value="1" x-model="dashboardAutoSwitch" @checked(old('dashboard_chart_auto_switch_on_new_active_period', $dashboardChartAutoSwitchOnNewActivePeriod)) class="mt-0.5 rounded border-slate-300 text-sky-600 shadow-sm focus:ring-sky-500" />
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
            </div>

            {{-- New Period Section --}}
            <div class="overflow-hidden rounded-2xl border border-amber-100 bg-white shadow-sm" x-data="{ open: false }">
                <div class="flex flex-col gap-3 border-b border-amber-100 bg-amber-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                    <div class="flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="ui-card-header-icon text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                        </svg>
                        <h3 class="ui-card-header-title text-amber-900">Mulai Periode Baru</h3>
                    </div>
                    <button type="button" class="ui-btn w-full bg-amber-600 px-4 py-2 text-white shadow-sm hover:bg-amber-700 focus:ring-amber-500 sm:w-auto" @click="open = !open">
                        <span x-show="!open">Buka Form</span>
                        <span x-show="open" x-cloak>Tutup Form</span>
                    </button>
                </div>
                <div class="p-4 sm:p-5" x-show="open" x-cloak>
                    <div class="ui-settings-panel ui-settings-panel-amber p-3 text-sm text-amber-900 sm:p-4">
                        <p class="font-bold">Untuk pergantian periode resmi.</p>
                        <p class="mt-1 text-xs text-amber-700">Backup database sebelum lanjut.</p>
                    </div>

                    <form method="POST" action="{{ route('internal.settings.period.startNew') }}" class="mt-4 space-y-4" x-data="{ year: @js((string) old('new_year', $activeYear + 1)), confirmYear: @js((string) old('new_year_confirmation')), backup: @js((bool) old('backup_confirmed')) }">
                        @csrf

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-1" for="new_year">Tahun Baru</label>
                                <input id="new_year" name="new_year" type="number" min="2000" max="2100" x-model="year" class="ui-input w-full" required />
                                <x-input-error class="mt-2" :messages="$errors->get('new_year')" />
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-1" for="new_year_confirmation">Ketik Ulang Tahun</label>
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
