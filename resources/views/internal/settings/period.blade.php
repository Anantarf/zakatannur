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
                            Mohon periksa:
                        </div>
                        <ul class="list-disc pl-10 space-y-1 text-sm">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            @php
                $errorToSection = [
                    'public_refresh_interval_seconds' => 1,
                    'period_label' => 2, 'period_starts_at' => 2, 'period_ends_at' => 2, 'hijri_year' => 2,
                    'default_fitrah_cash_per_jiwa' => 3, 'default_fitrah_beras_per_jiwa' => 3,
                    'default_fidyah_per_hari' => 3, 'default_fidyah_beras_per_hari' => 3,
                    'nishab_gold_gram' => 3, 'gold_price_per_gram' => 3,
                    'chart_starts_at' => 4, 'chart_ends_at' => 4, 'chart_fallback_buffer_days' => 4,
                    'dashboard_chart_mode' => 4, 'dashboard_chart_period_id' => 4,
                    'dashboard_chart_starts_at' => 4, 'dashboard_chart_ends_at' => 4,
                ];
                $firstErrorSection = null;
                foreach ($errorToSection as $field => $section) {
                    if ($errors->has($field)) { $firstErrorSection = $section; break; }
                }
            @endphp
            <div class="ui-card overflow-hidden"
                x-data="{
                    tab: '{{ ($errors->hasAny(["new_year", "new_year_confirmation", "backup_confirmed"])) ? "new_period" : "config" }}',
                    expandedSection: {{ $firstErrorSection ?? 'null' }},
                    isDirty: {{ $errors->any() ? 'true' : 'false' }},
                    isSubmitting: false,
                    dashboardMode: @js(old('dashboard_chart_mode', $dashboardChartMode)),
                    dashboardArchive: @js((bool) old('dashboard_chart_show_offseason_archive', $dashboardChartShowOffseasonArchive)),
                    dashboardAutoSwitch: @js((bool) old('dashboard_chart_auto_switch_on_new_active_period', $dashboardChartAutoSwitchOnNewActivePeriod)),
                    toggleSection(sectionNum) {
                        this.expandedSection = this.expandedSection === sectionNum ? null : sectionNum;
                    },
                    year: @js((string) old('new_year', $activeYear + 1)),
                    confirmYear: @js((string) old('new_year_confirmation')),
                    backup: @js((bool) old('backup_confirmed')),
                    init() {
                        window.addEventListener('beforeunload', (e) => {
                            if (this.isDirty) {
                                e.preventDefault();
                                e.returnValue = '';
                            }
                        });
                    },
                }"
                @keydown.escape="expandedSection = null">

                <div class="border-b border-slate-100 bg-white px-4 py-3 sm:px-5">
                    <div class="flex w-full rounded-lg border border-slate-200 bg-slate-100/60 p-0.5 sm:w-fit">
                        <button type="button" @click="tab = 'config'"
                            :class="tab === 'config' ? 'bg-white shadow-sm text-slate-900' : 'text-slate-500 hover:text-slate-700'"
                            class="flex-1 rounded-button px-3 py-1.5 text-sm font-semibold transition sm:flex-none">
                            Edit Periode Aktif
                        </button>
                        <button type="button" @click="tab = 'new_period'"
                            :class="tab === 'new_period' ? 'bg-amber-600 text-white shadow-sm' : 'text-slate-500 hover:text-amber-700'"
                            class="flex-1 rounded-button px-3 py-1.5 text-sm font-semibold transition sm:flex-none">
                            Buka Tahun Baru
                        </button>
                    </div>
                </div>

                {{-- Tab: Konfigurasi --}}
                <div x-show="tab === 'config'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="p-4 sm:p-5">
                    <form method="POST" action="{{ route('internal.settings.period.update') }}" class="space-y-3" @submit="isDirty = false; isSubmitting = true" @input="isDirty = true" @change="isDirty = true">
                        @csrf

                        {{-- Section 1: Dasar Sistem (Accordion) --}}
                        <section class="ui-settings-panel ui-settings-panel-muted">
                            <button type="button" @click="toggleSection(1)" class="ui-settings-section-head group w-full text-left hover:bg-slate-50/50 transition-colors">
                                <span class="ui-section-accent"></span>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <div class="flex-1">
                                    <h4 class="ui-settings-section-title">Dasar Sistem</h4>
                                    <p class="ui-settings-section-copy" x-show="expandedSection === 1" x-cloak>Tahun aktif sistem dan interval refresh halaman publik.</p>
                                    <p class="text-sm text-slate-500 mt-0.5" x-show="expandedSection !== 1" x-cloak>Tahun {{ old('active_year', $activeYear) }} &middot; Refresh {{ old('public_refresh_interval_seconds', $publicRefreshIntervalSeconds ?? 15) }}s</p>
                                </div>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-400 transition-transform" :class="expandedSection === 1 ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div x-show="expandedSection === 1" x-collapse class="pt-4">
                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <div>
                                        <p class="ui-form-label">Tahun Aktif Sistem</p>
                                        <div class="ui-input flex items-center justify-between gap-2 bg-slate-50 text-slate-700 cursor-default select-none">
                                            <span class="font-semibold">{{ old('active_year', $activeYear) }}</span>
                                            <button type="button" @click="tab = 'new_period'" class="text-xs text-brand-600 hover:text-brand-800 font-medium shrink-0">Buka Tahun Baru →</button>
                                        </div>
                                        <input name="active_year" type="hidden" value="{{ old('active_year', $activeYear) }}" />
                                    </div>
                                    <div>
                                        <label class="ui-form-label" for="public_refresh_interval_seconds">Refresh Halaman Publik (detik)</label>
                                        <input id="public_refresh_interval_seconds" name="public_refresh_interval_seconds" type="number" min="0" max="{{ (int) config('zakat.public_refresh.form_max_seconds', 600) }}" value="{{ old('public_refresh_interval_seconds', $publicRefreshIntervalSeconds ?? 15) }}" class="ui-input w-full" @change="isDirty = true" required />
                                        <p class="mt-1 text-xs leading-5 text-slate-500">0 = tidak refresh, 15 = refresh tiap 15 detik</p>
                                        <x-input-error class="mt-2" :messages="$errors->get('public_refresh_interval_seconds')" />
                                    </div>
                                </div>
                            </div>
                        </section>

                        {{-- Section 2: Identitas Periode (Accordion) --}}
                        <section class="ui-settings-panel ui-settings-panel-muted">
                            <button type="button" @click="toggleSection(2)" class="ui-settings-section-head group w-full text-left hover:bg-slate-50/50 transition-colors">
                                <span class="ui-section-accent"></span>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <div class="flex-1">
                                    <h4 class="ui-settings-section-title">Identitas Periode Aktif</h4>
                                    <p class="ui-settings-section-copy" x-show="expandedSection === 2" x-cloak>Nama, tahun Hijriah, dan rentang penerimaan.</p>
                                    <p class="text-sm text-slate-500 mt-0.5" x-show="expandedSection !== 2" x-cloak>{{ $activePeriod?->display_label ?? $activeYear }}@if($activePeriod?->starts_at) &middot; {{ $activePeriod->starts_at->translatedFormat('d M') }} – {{ optional($activePeriod->ends_at)->translatedFormat('d M Y') ?? '?' }}@endif</p>
                                </div>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-400 transition-transform" :class="expandedSection === 2 ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div x-show="expandedSection === 2" x-collapse class="pt-4">
                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <div class="sm:col-span-2">
                                        <label class="ui-form-label" for="period_label">Nama Periode</label>
                                        <input id="period_label" name="period_label" type="text" maxlength="80" value="{{ old('period_label', $activePeriod?->label ?? ('Ramadan ' . $activeYear)) }}" class="ui-input w-full" placeholder="Contoh: Ramadan 2026" @change="isDirty = true" />
                                        <x-input-error class="mt-2" :messages="$errors->get('period_label')" />
                                    </div>
                                    <div>
                                        <label class="ui-form-label" for="hijri_year">Tahun Hijriah (opsional)</label>
                                        <input id="hijri_year" name="hijri_year" type="number" min="1300" max="1600" value="{{ old('hijri_year', $activePeriod?->hijri_year) }}" class="ui-input w-full" placeholder="Contoh: 1451" />
                                        <x-input-error class="mt-2" :messages="$errors->get('hijri_year')" />
                                    </div>
                                    <div>
                                        <label class="ui-form-label" for="period_starts_at">Tanggal Mulai Penerimaan</label>
                                        <input id="period_starts_at" name="period_starts_at" type="date" value="{{ old('period_starts_at', optional($activePeriod?->starts_at)->toDateString()) }}" class="ui-input w-full" />
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
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <div class="flex-1">
                                    <h4 class="ui-settings-section-title">Nominal Default Transaksi & Zakat Mal</h4>
                                    <p class="ui-settings-section-copy" x-show="expandedSection === 3" x-cloak>Isian awal saat panitia input transaksi baru dan acuan nishab AI.</p>
                                    <p class="text-sm text-slate-500 mt-0.5" x-show="expandedSection !== 3" x-cloak>Fitrah Rp {{ number_format($defaultFitrahCashPerJiwa, 0, ',', '.') }} &middot; Nishab {{ $nishabGoldGram ?? 85 }}g (Rp {{ number_format($goldPricePerGram ?? 900000, 0, ',', '.') }}/g)</p>
                                </div>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-400 transition-transform" :class="expandedSection === 3 ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div x-show="expandedSection === 3" x-collapse class="pt-4">
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
                                    <div>
                                        <label class="ui-form-label" for="nishab_gold_gram">Batas Nishab Emas (Gram)</label>
                                        <input id="nishab_gold_gram" name="nishab_gold_gram" type="number" min="1" value="{{ old('nishab_gold_gram', $nishabGoldGram ?? 85) }}" class="ui-input w-full" required />
                                        <x-input-error class="mt-2" :messages="$errors->get('nishab_gold_gram')" />
                                    </div>
                                    <div>
                                        <label class="ui-form-label" for="gold_price_per_gram">Harga Emas per Gram (Rp)</label>
                                        <input id="gold_price_per_gram" name="gold_price_per_gram" type="number" min="1" value="{{ old('gold_price_per_gram', $goldPricePerGram ?? 900000) }}" class="ui-input w-full" required />
                                        <x-input-error class="mt-2" :messages="$errors->get('gold_price_per_gram')" />
                                    </div>
                                </div>
                            </div>
                        </section>

                        {{-- Section 4: Pengaturan Grafik (Accordion — gabungan rentang periode + sumber dashboard) --}}
                        <section class="ui-settings-panel ui-settings-panel-muted">
                            <button type="button" @click="toggleSection(4)" class="ui-settings-section-head group w-full text-left hover:bg-slate-50/50 transition-colors">
                                <span class="ui-section-accent"></span>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                                <div class="flex-1">
                                    <h4 class="ui-settings-section-title">Pengaturan Grafik</h4>
                                    <p class="ui-settings-section-copy" x-show="expandedSection === 4" x-cloak>Rentang baca grafik periode aktif dan sumber data grafik dashboard.</p>
                                    <p class="text-sm text-slate-500 mt-0.5" x-show="expandedSection !== 4" x-cloak>{{ $chartRange['label'] ?? '-' }} &middot; Dashboard: {{ $dashboardChartRange['period_label'] ?? 'belum dipilih' }}</p>
                                </div>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-400 transition-transform" :class="expandedSection === 4 ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div x-show="expandedSection === 4" x-collapse class="pt-4 space-y-5">

                                {{-- Sub: Rentang Grafik Periode --}}
                                <div>
                                    <p class="ui-settings-kicker text-brand-700 mb-3">Rentang Grafik Periode Aktif</p>
                                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                        <div>
                                            <label class="ui-form-label" for="chart_starts_at">Tanggal Awal</label>
                                            <input id="chart_starts_at" name="chart_starts_at" type="date" value="{{ old('chart_starts_at', $chartStartsAt) }}" class="ui-input w-full" />
                                            <x-input-error class="mt-2" :messages="$errors->get('chart_starts_at')" />
                                        </div>
                                        <div>
                                            <label class="ui-form-label" for="chart_ends_at">Tanggal Akhir</label>
                                            <input id="chart_ends_at" name="chart_ends_at" type="date" value="{{ old('chart_ends_at', $chartEndsAt) }}" class="ui-input w-full" />
                                            <p class="mt-1 text-xs leading-5 text-emerald-700">Kosongkan agar ikut rentang periode.</p>
                                            <x-input-error class="mt-2" :messages="$errors->get('chart_ends_at')" />
                                        </div>
                                        <div>
                                            <label class="ui-form-label" for="chart_fallback_buffer_days">Jarak Ekstra (hari)</label>
                                            <input id="chart_fallback_buffer_days" name="chart_fallback_buffer_days" type="number" min="0" max="14" value="{{ old('chart_fallback_buffer_days', $chartFallbackBufferDays) }}" class="ui-input w-full" required />
                                            <p class="mt-1 text-xs leading-5 text-slate-500">Tambahan hari setelah akhir periode agar grafik tidak terpotong. 0 = tanpa jarak.</p>
                                            <x-input-error class="mt-2" :messages="$errors->get('chart_fallback_buffer_days')" />
                                        </div>
                                    </div>
                                </div>

                                <hr class="border-emerald-100" />

                                {{-- Sub: Sumber Grafik Dashboard --}}
                                <div>
                                    <p class="ui-settings-kicker text-brand-700 mb-3">Sumber Grafik Dashboard</p>

                                    @if (!empty($dashboardChartRange['fallback_note']))
                                        <div class="ui-alert ui-alert-warning mb-3">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="ui-alert-icon shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                                            <span class="text-xs font-medium">{{ $dashboardChartRange['fallback_note'] }}</span>
                                        </div>
                                    @endif

                                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-3 mb-4">
                                        @php
                                            $dashboardChartModeDescriptions = [
                                                \App\Services\Charts\ChartRangeResolver::DASHBOARD_MODE_ACTIVE_PERIOD => 'Ikut periode aktif (paling aman untuk harian)',
                                                \App\Services\Charts\ChartRangeResolver::DASHBOARD_MODE_MANUAL_PERIOD => 'Kunci ke periode tertentu (review/arsip)',
                                                \App\Services\Charts\ChartRangeResolver::DASHBOARD_MODE_LAST_COMPLETED_PERIOD => 'Periode terakhir selesai (di luar musim)',
                                            ];
                                        @endphp
                                        @foreach ($dashboardChartModes as $modeValue => $modeLabel)
                                            <label class="ui-settings-choice text-sm"
                                                :class="dashboardMode === '{{ $modeValue }}'
                                                    ? 'border-brand-300 ring-2 ring-brand-200'
                                                    : 'border-slate-200 hover:border-brand-200'">
                                                <input type="radio" name="dashboard_chart_mode" value="{{ $modeValue }}" x-model="dashboardMode" class="sr-only" />
                                                <span class="block font-bold text-slate-900">{{ $modeLabel }}</span>
                                                <span class="ui-settings-choice-copy text-xs">{{ $dashboardChartModeDescriptions[$modeValue] ?? '' }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                    <x-input-error class="mt-2" :messages="$errors->get('dashboard_chart_mode')" />

                                    <div x-show="dashboardMode === '{{ \App\Services\Charts\ChartRangeResolver::DASHBOARD_MODE_MANUAL_PERIOD }}'" x-transition class="mb-4">
                                        @php
                                            $dashPeriodOptions = [];
                                            foreach ($dashboardChartPeriods as $periodOption) {
                                                $dashPeriodOptions[$periodOption->id] = $periodOption->display_label . ($periodOption->sequence > 1 ? ' #' . $periodOption->sequence : '');
                                            }
                                        @endphp
                                        <label class="ui-form-label" for="dashboard_chart_period_id">Periode yang Dikunci</label>
                                        <x-ui-select-custom name="dashboard_chart_period_id" :options="$dashPeriodOptions" :value="old('dashboard_chart_period_id', $dashboardChartPeriodId)" placeholder="Pilih periode" />
                                        <x-input-error class="mt-2" :messages="$errors->get('dashboard_chart_period_id')" />
                                    </div>

                                    <div x-show="dashboardMode === '{{ \App\Services\Charts\ChartRangeResolver::DASHBOARD_MODE_MANUAL_PERIOD }}'" x-transition class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                        <div>
                                            <label class="ui-form-label" for="dashboard_chart_starts_at">Timpa Tanggal Awal (opsional)</label>
                                            <input id="dashboard_chart_starts_at" name="dashboard_chart_starts_at" type="date" value="{{ old('dashboard_chart_starts_at', $dashboardChartStartsAt) }}" class="ui-input w-full" />
                                            <x-input-error class="mt-2" :messages="$errors->get('dashboard_chart_starts_at')" />
                                        </div>
                                        <div>
                                            <label class="ui-form-label" for="dashboard_chart_ends_at">Timpa Tanggal Akhir (opsional)</label>
                                            <input id="dashboard_chart_ends_at" name="dashboard_chart_ends_at" type="date" value="{{ old('dashboard_chart_ends_at', $dashboardChartEndsAt) }}" class="ui-input w-full" />
                                            <x-input-error class="mt-2" :messages="$errors->get('dashboard_chart_ends_at')" />
                                        </div>
                                    </div>

                                    <div class="space-y-2">
                                        <label class="ui-settings-check border-emerald-100 text-emerald-900">
                                            <input type="checkbox" name="dashboard_chart_show_offseason_archive" value="1" x-model="dashboardArchive" class="mt-0.5 rounded border-slate-300 text-emerald-600 shadow-sm focus:ring-emerald-500" />
                                            <span class="text-sm font-medium">Tetap tampilkan grafik arsip di luar musim zakat</span>
                                        </label>
                                        <label class="ui-settings-check border-emerald-100 text-emerald-900">
                                            <input type="checkbox" name="dashboard_chart_auto_switch_on_new_active_period" value="1" x-model="dashboardAutoSwitch" class="mt-0.5 rounded border-slate-300 text-emerald-600 shadow-sm focus:ring-emerald-500" />
                                            <span class="text-sm font-medium">Otomatis ganti ke periode aktif baru</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <div class="mt-6 border-t border-slate-100 pt-5">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-end">
                                <p class="text-xs text-slate-400 sm:mr-auto" x-show="!isDirty && !isSubmitting" x-cloak>Belum ada perubahan.</p>
                                <a href="{{ route('dashboard') }}" class="ui-btn ui-btn-secondary w-full sm:w-auto">Kembali ke Dashboard</a>
                                <button type="submit"
                                        :disabled="isSubmitting || !isDirty"
                                        :class="{'opacity-50 cursor-not-allowed': !isDirty}"
                                        class="ui-btn ui-btn-primary w-full px-6 py-3 sm:w-auto">
                                    <template x-if="isSubmitting">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                                        </svg>
                                    </template>
                                    <template x-if="!isSubmitting">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </template>
                                    <span x-text="isSubmitting ? 'Menyimpan...' : 'Simpan Pengaturan'"></span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                {{-- Tab: Mulai Periode Baru --}}
                <div x-show="tab === 'new_period'" x-cloak x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="p-4 sm:p-5">
                    <div class="ui-settings-panel ui-settings-panel-amber p-3 text-sm text-amber-900 sm:p-4">
                        <p class="font-bold">Gunakan hanya saat benar-benar masuk periode/tahun zakat baru.</p>
                        <p class="mt-1 text-xs leading-5 text-amber-700">Aksi ini membuat periode aktif baru dan mengubah default sistem. Backup database sebelum lanjut.</p>
                    </div>

                    <form method="POST" action="{{ route('internal.settings.period.start_new') }}" class="mt-4 space-y-4" @submit="isDirty = false">
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
                            <button type="submit" :disabled="String(year) !== String(confirmYear) || !backup" class="ui-btn ui-btn-warning w-full sm:w-auto">
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
