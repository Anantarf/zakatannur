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
                    expandedSection: null,
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
                    toggleSection(sectionNum) {
                        this.expandedSection = this.expandedSection === sectionNum ? null : sectionNum;
                    },
                    year: @js((string) old('new_year', $activeYear + 1)),
                    confirmYear: @js((string) old('new_year_confirmation')),
                    backup: @js((bool) old('backup_confirmed')),
                }"
                @keydown.escape="expandedSection = null">

                <div class="border-b border-slate-100 bg-white px-4 py-3 sm:px-5">
                    <div class="flex w-full rounded-lg border border-slate-200 bg-slate-100/60 p-0.5 sm:w-fit">
                        <button type="button" @click="tab = 'config'"
                            :class="tab === 'config' ? 'bg-white shadow-sm text-slate-900' : 'text-slate-500 hover:text-slate-700'"
                            class="flex-1 rounded-md px-3 py-1.5 text-sm font-semibold transition sm:flex-none">
                            Edit Periode Aktif
                        </button>
                        <button type="button" @click="tab = 'new_period'"
                            :class="tab === 'new_period' ? 'bg-amber-600 text-white shadow-sm' : 'text-slate-500 hover:text-amber-700'"
                            class="flex-1 rounded-md px-3 py-1.5 text-sm font-semibold transition sm:flex-none">
                            Buka Tahun Baru
                        </button>
                    </div>
                </div>

                {{-- Tab: Konfigurasi --}}
                <div x-show="tab === 'config'" class="p-4 sm:p-5">
                    <div class="mb-6">
                        <div class="ui-settings-kicker text-brand-700 mb-3">Status Saat Ini</div>
                        <div class="grid grid-cols-1 gap-3 text-sm md:grid-cols-3">
                            <button type="button" @click="toggleSection(2)" class="ui-settings-summary-card ui-settings-summary-card-emerald text-left hover:ring-2 hover:ring-brand-300 transition-all cursor-pointer">
                                <div class="text-[11px] font-bold text-brand-700 flex items-center justify-between">
                                    <span>Periode Aktif</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" :class="expandedSection === 2 ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </div>
                                <div class="mt-1 text-base font-bold text-brand-950">{{ $activePeriod?->display_label ?? $activeYear }}</div>
                                <div class="mt-1 text-xs leading-5 text-brand-800">
                                    Klik untuk edit identitas & tanggal
                                </div>
                            </button>
                            <button type="button" @click="toggleSection(4)" class="ui-settings-summary-card ui-settings-summary-card-emerald text-left hover:ring-2 hover:ring-brand-300 transition-all cursor-pointer">
                                <div class="text-[11px] font-bold text-brand-700 flex items-center justify-between">
                                    <span>Grafik Periode</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" :class="expandedSection === 4 ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </div>
                                <div class="mt-1 text-base font-bold text-brand-950">{{ $chartRange['label'] ?? '-' }}</div>
                                <div class="mt-1 text-xs leading-5 text-brand-800">
                                    Klik untuk adjust tanggal grafik
                                </div>
                            </button>
                            <button type="button" @click="toggleSection(5)" class="ui-settings-summary-card ui-settings-summary-card-emerald text-left hover:ring-2 hover:ring-brand-300 transition-all cursor-pointer">
                                <div class="text-[11px] font-bold text-brand-700 flex items-center justify-between">
                                    <span>Sumber Dashboard</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" :class="expandedSection === 5 ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </div>
                                <div class="mt-1 text-base font-bold text-brand-950">{{ $dashboardChartRange['period_label'] ?? 'Belum dipilih' }}</div>
                                <div class="mt-0.5 text-[11px] text-brand-700">
                                    Klik untuk ubah mode & periode
                                </div>
                            </button>
                            <button type="button" @click="toggleSection(6)" class="ui-settings-summary-card ui-settings-summary-card-emerald text-left hover:ring-2 hover:ring-brand-300 transition-all cursor-pointer">
                                <div class="text-[11px] font-bold text-brand-700 flex items-center justify-between">
                                    <span>Pusher Realtime</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" :class="expandedSection === 6 ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </div>
                                <div class="mt-1 text-base font-bold text-brand-950">Test Broadcast</div>
                                <div class="mt-0.5 text-[11px] text-brand-700">
                                    Klik untuk test koneksi
                                </div>
                            </button>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('internal.settings.period.update') }}" class="space-y-3">
                        @csrf

                        {{-- Section 1: Dasar Sistem (Always Visible) --}}
                        <section class="ui-settings-panel ui-settings-panel-muted">
                            <div class="ui-settings-section-head">
                                <span class="ui-section-accent"></span>
                                <div>
                                    <h4 class="ui-settings-section-title">Dasar Sistem</h4>
                                    <p class="ui-settings-section-copy">Info sistem dan interval refresh. Tahun aktif hanya bisa diubah via "Buka Tahun Baru".</p>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="ui-form-label flex items-center gap-2" for="active_year">
                                        Tahun Aktif Sistem
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" title="Gunakan tab 'Buka Tahun Baru' untuk mengubah ini">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </label>
                                    <input id="active_year" name="active_year" type="number" min="2000" max="2100" value="{{ old('active_year', $activeYear) }}" class="ui-input w-full bg-slate-50" readonly required />
                                </div>
                                <div>
                                    <label class="ui-form-label flex items-center gap-2" for="public_refresh_interval_seconds">
                                        Refresh Halaman Publik (detik)
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" title="0 = tidak ada refresh otomatis, 15 = refresh tiap 15 detik">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </label>
                                    <input id="public_refresh_interval_seconds" name="public_refresh_interval_seconds" type="number" min="0" max="60" value="{{ old('public_refresh_interval_seconds', $publicRefreshIntervalSeconds ?? 15) }}" class="ui-input w-full" required />
                                    <p class="mt-1 text-xs leading-5 text-slate-500">0 = tidak refresh, 15 = refresh tiap 15 detik</p>
                                    <x-input-error class="mt-2" :messages="$errors->get('public_refresh_interval_seconds')" />
                                </div>
                            </div>
                        </section>

                        {{-- Section 2: Identitas Periode (Accordion) --}}
                        <section class="ui-settings-panel ui-settings-panel-muted">
                            <button type="button" @click="toggleSection(2)" class="ui-settings-section-head group w-full text-left hover:bg-slate-50/50 transition-colors">
                                <span class="ui-section-accent"></span>
                                <div class="flex-1">
                                    <h4 class="ui-settings-section-title">Identitas Periode Aktif</h4>
                                    <p class="ui-settings-section-copy">Nama, tahun Hijriah, dan rentang penerimaan.</p>
                                </div>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-400 transition-transform" :class="expandedSection === 2 ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div x-show="expandedSection === 2" x-transition class="pt-4">
                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <div class="sm:col-span-2">
                                        <label class="ui-form-label" for="period_label">Nama Periode</label>
                                        <input id="period_label" name="period_label" type="text" maxlength="80" value="{{ old('period_label', $activePeriod?->label ?? ('Ramadan ' . $activeYear)) }}" class="ui-input w-full" placeholder="Contoh: Ramadan 2026" />
                                        <x-input-error class="mt-2" :messages="$errors->get('period_label')" />
                                    </div>
                                    <div>
                                        <label class="ui-form-label" for="hijri_year">Tahun Hijriah (opsional)</label>
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
                            </div>
                        </section>

                        {{-- Section 3: Nominal Default (Accordion) --}}
                        <section class="ui-settings-panel ui-settings-panel-muted">
                            <button type="button" @click="toggleSection(3)" class="ui-settings-section-head group w-full text-left hover:bg-slate-50/50 transition-colors">
                                <span class="ui-section-accent"></span>
                                <div class="flex-1">
                                    <h4 class="ui-settings-section-title">Nominal Default Transaksi</h4>
                                    <p class="ui-settings-section-copy">Isian awal saat panitia input transaksi baru. Transaksi lama tidak otomatis dihitung ulang.</p>
                                </div>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-400 transition-transform" :class="expandedSection === 3 ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div x-show="expandedSection === 3" x-transition class="pt-4">
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
                            </div>
                        </section>
                        {{-- Section 4: Rentang Grafik (Accordion) --}}
                        <section class="ui-settings-panel ui-settings-panel-emerald">
                            <button type="button" @click="toggleSection(4)" class="ui-settings-section-head group w-full text-left hover:bg-emerald-50/50 transition-colors">
                                <span class="ui-section-accent"></span>
                                <div class="flex-1">
                                    <h4 class="ui-settings-section-title">Rentang Grafik Periode Aktif</h4>
                                    <p class="ui-settings-section-copy">Tanggal yang dipakai untuk baca grafik periode. Kosongkan untuk ikuti rentang periode.</p>
                                </div>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-400 transition-transform" :class="expandedSection === 4 ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div x-show="expandedSection === 4" x-transition class="pt-4">
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
                                        <label class="ui-form-label" for="chart_fallback_buffer_days">Buffer Hari Setelah</label>
                                        <input id="chart_fallback_buffer_days" name="chart_fallback_buffer_days" type="number" min="0" max="14" value="{{ old('chart_fallback_buffer_days', $chartFallbackBufferDays) }}" class="ui-input w-full" required />
                                        <p class="mt-1 text-xs leading-5 text-emerald-700">Agar grafik tidak berhenti tepat di tanggal selesai.</p>
                                        <x-input-error class="mt-2" :messages="$errors->get('chart_fallback_buffer_days')" />
                                    </div>
                                </div>
                            </div>
                        </section>

                        {{-- Section 5: Sumber Grafik Dashboard (Accordion) --}}
                        <section class="ui-settings-panel ui-settings-panel-emerald">
                            <button type="button" @click="toggleSection(5)" class="ui-settings-section-head group w-full text-left hover:bg-emerald-50/50 transition-colors">
                                <span class="ui-section-accent"></span>
                                <div class="flex-1">
                                    <h4 class="ui-settings-section-title">Sumber Grafik Dashboard</h4>
                                    <p class="ui-settings-section-copy">Pilih apakah dashboard ikut periode aktif, periode manual, atau periode terakhir selesai.</p>
                                </div>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-400 transition-transform" :class="expandedSection === 5 ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div x-show="expandedSection === 5" x-transition class="pt-4">
                                <div class="mb-4 rounded-xl bg-emerald-50 border border-emerald-100 p-3">
                                    <div class="text-xs font-bold text-emerald-700 mb-1">📍 Status Grafik Saat Ini</div>
                                    <div class="text-sm font-bold text-slate-900">{{ $dashboardChartRange['period_label'] ?? 'Belum ada periode' }}</div>
                                    <div class="text-xs text-slate-600 mt-1">{{ $dashboardChartRange['label'] ?? 'Akan mengikuti pengaturan yang disimpan' }}</div>
                                    @if (!empty($dashboardChartRange['fallback_note']))
                                        <div class="mt-2 text-xs text-amber-700 bg-amber-50 border border-amber-100 rounded px-2 py-1">
                                            ⚠️ {{ $dashboardChartRange['fallback_note'] }}
                                        </div>
                                    @endif
                                </div>

                                <div class="mb-4">
                                    <div class="ui-form-label mb-3 font-bold">Pilih Mode</div>
                                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                                        @php
                                            $dashboardChartModeDescriptions = [
                                                \App\Services\Charts\ChartRangeResolver::DASHBOARD_MODE_ACTIVE_PERIOD => 'Dashboard ikut periode aktif (paling aman untuk harian)',
                                                \App\Services\Charts\ChartRangeResolver::DASHBOARD_MODE_MANUAL_PERIOD => 'Kunci ke periode tertentu (review/arsip)',
                                                \App\Services\Charts\ChartRangeResolver::DASHBOARD_MODE_LAST_COMPLETED_PERIOD => 'Periode terakhir selesai (di luar musim)',
                                            ];
                                        @endphp
                                        @foreach ($dashboardChartModes as $modeValue => $modeLabel)
                                            <label class="ui-settings-choice text-sm"
                                                :class="dashboardMode === '{{ $modeValue }}'
                                                    ? 'border-emerald-300 ring-2 ring-emerald-200'
                                                    : 'border-slate-200 hover:border-emerald-200'">
                                                <input type="radio" name="dashboard_chart_mode" value="{{ $modeValue }}" x-model="dashboardMode" class="sr-only" />
                                                <span class="block font-bold text-slate-900">{{ $modeLabel }}</span>
                                                <span class="ui-settings-choice-copy text-xs">{{ $dashboardChartModeDescriptions[$modeValue] ?? '' }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                    <x-input-error class="mt-2" :messages="$errors->get('dashboard_chart_mode')" />
                                </div>

                                <div x-show="dashboardMode === '{{ \App\Services\Charts\ChartRangeResolver::DASHBOARD_MODE_MANUAL_PERIOD }}'" x-transition class="mb-4">
                                    <label class="ui-form-label" for="dashboard_chart_period_id">Periode yang Dikunci</label>
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

                                <div class="mb-4 p-3 rounded-lg bg-slate-50 border border-slate-200 text-sm">
                                    <span x-show="dashboardMode === '{{ \App\Services\Charts\ChartRangeResolver::DASHBOARD_MODE_ACTIVE_PERIOD }}'">
                                        ✓ Dashboard otomatis ikut periode aktif.
                                    </span>
                                    <span x-show="dashboardMode === '{{ \App\Services\Charts\ChartRangeResolver::DASHBOARD_MODE_MANUAL_PERIOD }}'" x-cloak>
                                        ✓ Dashboard memakai periode pilihan sampai diubah.
                                    </span>
                                    <span x-show="dashboardMode === '{{ \App\Services\Charts\ChartRangeResolver::DASHBOARD_MODE_LAST_COMPLETED_PERIOD }}'" x-cloak>
                                        ✓ Dashboard memakai periode terakhir selesai.
                                    </span>
                                </div>

                                <div class="mb-4">
                                    <label class="ui-form-label block mb-2 font-bold">Opsi Tambahan</label>
                                    <div class="space-y-2">
                                        <label class="ui-settings-check border-emerald-100 text-emerald-900">
                                            <input type="checkbox" name="dashboard_chart_show_offseason_archive" value="1" x-model="dashboardArchive" @checked(old('dashboard_chart_show_offseason_archive', $dashboardChartShowOffseasonArchive)) class="mt-0.5 rounded border-slate-300 text-emerald-600 shadow-sm focus:ring-emerald-500" />
                                            <span class="text-sm font-medium">Tetap tampilkan grafik arsip di luar musim zakat</span>
                                        </label>
                                        <label class="ui-settings-check border-emerald-100 text-emerald-900">
                                            <input type="checkbox" name="dashboard_chart_auto_switch_on_new_active_period" value="1" x-model="dashboardAutoSwitch" @checked(old('dashboard_chart_auto_switch_on_new_active_period', $dashboardChartAutoSwitchOnNewActivePeriod)) class="mt-0.5 rounded border-slate-300 text-emerald-600 shadow-sm focus:ring-emerald-500" />
                                            <span class="text-sm font-medium">Otomatis ganti ke periode aktif baru</span>
                                        </label>
                                    </div>
                                </div>

                                <div x-show="dashboardMode === '{{ \App\Services\Charts\ChartRangeResolver::DASHBOARD_MODE_MANUAL_PERIOD }}'" x-transition class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    <div>
                                        <label class="ui-form-label" for="dashboard_chart_starts_at">Override Tanggal Awal (opsional)</label>
                                        <input id="dashboard_chart_starts_at" name="dashboard_chart_starts_at" type="date" value="{{ old('dashboard_chart_starts_at', $dashboardChartStartsAt) }}" class="ui-input w-full" />
                                        <x-input-error class="mt-2" :messages="$errors->get('dashboard_chart_starts_at')" />
                                    </div>
                                    <div>
                                        <label class="ui-form-label" for="dashboard_chart_ends_at">Override Tanggal Akhir (opsional)</label>
                                        <input id="dashboard_chart_ends_at" name="dashboard_chart_ends_at" type="date" value="{{ old('dashboard_chart_ends_at', $dashboardChartEndsAt) }}" class="ui-input w-full" />
                                        <x-input-error class="mt-2" :messages="$errors->get('dashboard_chart_ends_at')" />
                                    </div>
                                </div>
                            </div>
                        </section>

                        {{-- Section 6: Test Pusher (Accordion) --}}
                        <section class="ui-settings-panel ui-settings-panel-emerald">
                            <button type="button" @click="toggleSection(6)" class="ui-settings-section-head group w-full text-left hover:bg-emerald-50/50 transition-colors">
                                <span class="ui-section-accent"></span>
                                <div class="flex-1">
                                    <h4 class="ui-settings-section-title">Test Koneksi Pusher</h4>
                                    <p class="ui-settings-section-copy">Gunakan tombol ini untuk menembakkan event transaksi palsu untuk mengetes update realtime di halaman depan.</p>
                                </div>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-400 transition-transform" :class="expandedSection === 6 ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div x-show="expandedSection === 6" x-transition class="pt-4">
                                <div class="mb-4 rounded-xl bg-emerald-50 border border-emerald-100 p-4">
                                    <p class="text-sm text-emerald-900 mb-3"><strong>Langkah Pengetesan:</strong> Buka halaman beranda publik di tab/jendela baru. Lalu tekan tombol di bawah ini. Jika Pusher aktif, Anda akan mendengar suara 'Pop' dan angka total di beranda akan berkedip hijau seketika tanpa perlu memuat ulang halaman.</p>
                                    <button type="button" onclick="document.getElementById('form-test-pusher').submit();" class="ui-btn ui-btn-primary bg-indigo-600 hover:bg-indigo-700 text-white">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                        </svg>
                                        Tembakkan Event Pusher Sekarang
                                    </button>
                                </div>
                            </div>
                        </section>

                        <div class="mt-6 border-t border-slate-100 pt-5">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-sm font-bold text-slate-900">Siap menyimpan perubahan?</p>
                                    <p class="mt-1 text-xs leading-5 text-slate-500">Semua perubahan di bawah akan disimpan. Membuka tahun baru ada di tab terpisah.</p>
                                </div>
                                <div class="flex flex-col items-stretch gap-2 sm:flex-row sm:items-center">
                                    <a href="{{ route('dashboard') }}" class="ui-btn ui-btn-secondary w-full sm:w-auto">Kembali</a>
                                    <button type="submit" class="ui-btn ui-btn-primary w-full px-6 py-3 sm:w-auto">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        Simpan Pengaturan
                                    </button>
                                </div>
                            </div>
                            <p class="mt-4 text-center text-xs text-slate-400 sm:text-left">
                                Tahun tersedia: {{ implode(', ', $years) }}
                            </p>
                        </div>
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

    <form id="form-test-pusher" action="{{ route('internal.test_pusher') }}" method="POST" class="hidden">
        @csrf
    </form>
</x-app-layout>
