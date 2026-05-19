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
            <p class="ui-page-title-copy">Atur nilai zakat, rentang grafik, dan pergantian periode dengan kontrol yang aman.</p>
        </div>
    </x-slot>

    <div class="py-6 sm:py-10">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="ui-alert ui-alert-success">
                    <svg xmlns="http://www.w3.org/2000/svg" class="ui-alert-icon text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span class="font-medium">{{ session('status') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-red-900 shadow-sm">
                    <div class="flex items-center gap-2 font-bold text-red-700 mb-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
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
            @endif

            {{-- Settings Form --}}
            <div class="ui-card overflow-hidden">
                <div class="ui-card-header ui-card-header-emerald">
                    <svg xmlns="http://www.w3.org/2000/svg" class="ui-card-header-icon text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h3 class="ui-card-header-title text-emerald-900">Konfigurasi Periode</h3>
                </div>
                <div class="p-5 sm:p-6">
                    <div class="mb-6 rounded-[1.35rem] border border-emerald-100 bg-gradient-to-br from-emerald-50 via-white to-emerald-50/70 p-4 shadow-sm">
                        <div class="text-xs font-black uppercase tracking-[0.18em] text-emerald-700">Ringkasan Saat Ini</div>
                        <div class="mt-3 grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                            <div class="rounded-2xl border border-emerald-100/80 bg-white/80 p-4">
                                <div class="text-xs font-bold text-emerald-700">Periode Aktif</div>
                                <div class="mt-1 text-2xl font-black text-emerald-950">{{ $activePeriod?->display_label ?? $activeYear }}</div>
                                <div class="mt-0.5 text-[11px] text-emerald-700">Masehi {{ $activeYear }}{{ $activePeriod?->sequence > 1 ? ' #' . $activePeriod->sequence : '' }}</div>
                            </div>
                            <div class="rounded-2xl border border-emerald-100/80 bg-white/80 p-4">
                                <div class="text-xs font-bold text-emerald-700">Range Grafik Aktif</div>
                                <div class="mt-1 font-black text-emerald-950">{{ $chartRange['label'] ?? '-' }}</div>
                                <div class="mt-0.5 text-[11px] text-emerald-700">
                                    Sumber: {{ ($chartRange['source'] ?? '') === 'configured' ? 'diatur manual' : 'otomatis dari transaksi' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('internal.settings.period.update') }}" class="space-y-6">
                        @csrf

                        <div>
                            <label class="ui-form-label" for="active_year">Tahun Aktif</label>
                            <input id="active_year" name="active_year" type="number" min="2000" max="2100" value="{{ old('active_year', $activeYear) }}" class="ui-input w-full bg-gray-50" readonly required />
                            <p class="mt-1 text-xs text-gray-500">Tahun Aktif hanya bisa diubah lewat "Mulai Periode Baru".</p>
                            <x-input-error class="mt-2" :messages="$errors->get('active_year')" />
                        </div>

                        <hr class="border-gray-100" />

                        <div>
                            <div class="mb-4 flex items-center gap-2">
                                <span class="h-5 w-1 rounded-full bg-sky-500"></span>
                                <div>
                                    <h4 class="text-sm font-black text-slate-900">Identitas Periode</h4>
                                    <p class="text-xs text-slate-500">Gunakan Hijriah untuk membedakan Ramadan yang jatuh di tahun Masehi yang sama.</p>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                                <div class="sm:col-span-2">
                                    <label class="ui-form-label" for="period_label">Nama Periode</label>
                                    <input id="period_label" name="period_label" type="text" maxlength="80" value="{{ old('period_label', $activePeriod?->label ?? ('Ramadan ' . $activeYear)) }}" class="ui-input w-full" />
                                    <p class="mt-1 text-xs text-gray-500">Contoh: Ramadan 1451 H atau Ramadan Akhir 2030.</p>
                                    <x-input-error class="mt-2" :messages="$errors->get('period_label')" />
                                </div>
                                <div>
                                    <label class="ui-form-label" for="hijri_year">Tahun Hijriah</label>
                                    <input id="hijri_year" name="hijri_year" type="number" min="1300" max="1600" value="{{ old('hijri_year', $activePeriod?->hijri_year) }}" class="ui-input w-full" placeholder="Contoh: 1451" />
                                    <x-input-error class="mt-2" :messages="$errors->get('hijri_year')" />
                                </div>
                                <div>
                                    <label class="ui-form-label" for="hijri_month">Bulan Hijriah</label>
                                    <input id="hijri_month" name="hijri_month" type="number" min="1" max="12" value="{{ old('hijri_month', $activePeriod?->hijri_month ?? 9) }}" class="ui-input w-full" />
                                    <p class="mt-1 text-xs text-gray-500">Ramadan biasanya bulan ke-9.</p>
                                    <x-input-error class="mt-2" :messages="$errors->get('hijri_month')" />
                                </div>
                                <div>
                                    <label class="ui-form-label" for="period_starts_at">Mulai Periode</label>
                                    <input id="period_starts_at" name="period_starts_at" type="date" value="{{ old('period_starts_at', optional($activePeriod?->starts_at)->toDateString()) }}" class="ui-input w-full" />
                                    <x-input-error class="mt-2" :messages="$errors->get('period_starts_at')" />
                                </div>
                                <div>
                                    <label class="ui-form-label" for="period_ends_at">Selesai Periode</label>
                                    <input id="period_ends_at" name="period_ends_at" type="date" value="{{ old('period_ends_at', optional($activePeriod?->ends_at)->toDateString()) }}" class="ui-input w-full" />
                                    <x-input-error class="mt-2" :messages="$errors->get('period_ends_at')" />
                                </div>
                            </div>
                        </div>

                        <hr class="border-gray-100" />

                        <div>
                            <div class="mb-4 flex items-center gap-2">
                                <span class="h-5 w-1 rounded-full bg-emerald-500"></span>
                                <div>
                                    <h4 class="text-sm font-black text-slate-900">Nilai Default Zakat</h4>
                                    <p class="text-xs text-slate-500">Dipakai sebagai acuan saat petugas menginput transaksi baru.</p>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
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

                        <hr class="border-gray-100" />

                        <div class="rounded-[1.35rem] border border-emerald-100 bg-emerald-50/60 p-4 shadow-sm sm:p-5">
                            <div class="mb-4">
                                <h4 class="text-sm font-black text-emerald-900">Pengaturan Grafik</h4>
                                <p class="mt-1 text-xs text-emerald-700 leading-relaxed">Range ini hanya mengatur tampilan grafik, bukan membatasi input transaksi atau laporan.</p>
                            </div>
                            <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
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
                            <p class="mt-3 rounded-2xl border border-emerald-100 bg-white/75 px-3 py-2 text-xs text-emerald-700">Kosongkan tanggal jika ingin sistem memakai tanggal transaksi pertama-terakhir dengan tambahan hari otomatis.</p>
                        </div>

                        <hr class="border-gray-100" />

                        <div>
                            <label class="ui-form-label" for="public_refresh_interval_seconds">Refresh Data Publik (detik)</label>
                            <input id="public_refresh_interval_seconds" name="public_refresh_interval_seconds" type="number" min="0" max="60" value="{{ old('public_refresh_interval_seconds', $publicRefreshIntervalSeconds ?? 15) }}" class="ui-input w-full" required />
                            <p class="mt-1 text-xs text-gray-500">0 = mati. Rekomendasi saat puncak: 15 (boleh 10-60).</p>
                            <x-input-error class="mt-2" :messages="$errors->get('public_refresh_interval_seconds')" />
                        </div>

                        <div class="sticky bottom-3 z-10 -mx-2 rounded-2xl border border-emerald-100 bg-white/90 p-3 shadow-xl shadow-slate-900/10 backdrop-blur sm:static sm:mx-0 sm:flex sm:items-center sm:justify-between sm:shadow-none sm:backdrop-blur-0">
                            <p class="mb-3 hidden text-xs font-semibold text-slate-500 sm:mb-0 sm:block">Perubahan berlaku untuk periode aktif.</p>
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

                        <p class="text-center text-xs text-gray-400 sm:text-left">
                            Tahun yang tersedia saat ini: {{ implode(', ', $years) }}
                        </p>
                    </form>
                </div>
            </div>

            {{-- New Period Section --}}
            <div class="overflow-hidden rounded-2xl border border-red-100 bg-white shadow-sm">
                <div class="flex items-center gap-2 border-b border-red-100 bg-red-50 px-6 py-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="ui-card-header-icon text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                    </svg>
                    <h3 class="ui-card-header-title text-red-900">Zona Berisiko: Mulai Periode Baru</h3>
                </div>
                <div class="p-5 sm:p-6">
                    <p class="rounded-2xl border border-red-100 bg-red-50/70 p-4 text-sm leading-relaxed text-red-800">
                        Aksi ini akan mengubah Tahun Aktif ke tahun baru dan menyiapkan nilai awal tahun baru dari pengaturan tahun aktif saat ini.
                        <strong>Disarankan lakukan backup database terlebih dahulu.</strong>
                    </p>

                    <form method="POST" action="{{ route('internal.settings.period.startNew') }}" class="mt-5 space-y-5" x-data="{ year: @js((string) old('new_year', $activeYear + 1)), confirmYear: @js((string) old('new_year_confirmation')), backup: @js((bool) old('backup_confirmed')) }">
                        @csrf

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1" for="new_year">Tahun Baru</label>
                            <input id="new_year" name="new_year" type="number" min="2000" max="2100" x-model="year" class="ui-input w-full" required />
                            <p class="mt-1 text-xs text-gray-500">Boleh sama dengan Tahun Aktif jika ada Ramadan kedua dalam tahun Masehi yang sama.</p>
                            <x-input-error class="mt-2" :messages="$errors->get('new_year')" />
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1" for="new_year_confirmation">Ketik Tahun Baru untuk Konfirmasi</label>
                            <input id="new_year_confirmation" name="new_year_confirmation" type="text" inputmode="numeric" x-model="confirmYear" class="ui-input w-full focus:border-red-500 focus:ring-red-500" placeholder="Contoh: {{ $activeYear + 1 }}" required />
                            <p class="mt-1 text-xs text-gray-500">Ini mencegah perubahan periode karena salah klik.</p>
                            <x-input-error class="mt-2" :messages="$errors->get('new_year_confirmation')" />
                        </div>

                        <label class="flex items-start gap-3 rounded-xl border border-red-100 bg-red-50 p-4 text-sm">
                            <input type="checkbox" name="backup_confirmed" value="1" x-model="backup" class="mt-0.5 rounded border-gray-300 text-red-600 shadow-sm focus:ring-red-500" required />
                            <span class="text-red-800">Saya sudah backup database / siap menanggung risiko perubahan tahun aktif.</span>
                        </label>
                        <x-input-error class="mt-2" :messages="$errors->get('backup_confirmed')" />

                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <p class="text-xs text-red-700 sm:max-w-xs">Tombol aktif hanya jika tahun konfirmasi cocok dan checklist backup dicentang.</p>
                            <button type="submit" :disabled="String(year) !== String(confirmYear) || !backup" class="ui-btn ui-btn-danger w-full px-5 py-3 sm:w-auto">
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
