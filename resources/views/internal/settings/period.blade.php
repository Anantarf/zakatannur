<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <h2 class="font-bold text-xl sm:text-2xl text-emerald-800 leading-tight flex items-center justify-center sm:justify-start gap-2 text-center sm:text-left">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                Pengaturan Periode
            </h2>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-900 shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="bg-emerald-50 px-6 py-4 border-b border-emerald-100 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h3 class="font-bold text-emerald-900">Konfigurasi Periode</h3>
                </div>
                <div class="p-6">
                    <form method="POST" action="{{ route('internal.settings.period.update') }}" class="space-y-6">
                        @csrf

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1" for="active_year">Tahun Aktif</label>
                            <input id="active_year" name="active_year" type="number" min="2000" max="2100" value="{{ old('active_year', $activeYear) }}" class="w-full rounded-xl border-gray-200 bg-gray-50 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 transition-all" readonly required />
                            <p class="mt-1 text-xs text-gray-500">Tahun Aktif hanya bisa diubah lewat "Mulai Periode Baru".</p>
                        </div>

                        <hr class="border-gray-100" />

                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1" for="default_fitrah_cash_per_jiwa">Default Fitrah Cash / Jiwa (Rp)</label>
                                <input id="default_fitrah_cash_per_jiwa" name="default_fitrah_cash_per_jiwa" type="number" min="0" value="{{ old('default_fitrah_cash_per_jiwa', $defaultFitrahCashPerJiwa) }}" class="w-full rounded-xl border-gray-200 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 transition-all" required />
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1" for="default_fitrah_beras_per_jiwa">Default Fitrah Beras / Jiwa (Kg)</label>
                                <input id="default_fitrah_beras_per_jiwa" name="default_fitrah_beras_per_jiwa" type="number" step="0.01" min="0" value="{{ old('default_fitrah_beras_per_jiwa', $defaultFitrahBerasPerJiwa) }}" class="w-full rounded-xl border-gray-200 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 transition-all" required />
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1" for="default_fidyah_per_hari">Default Fidyah / Hari (Rp)</label>
                                <input id="default_fidyah_per_hari" name="default_fidyah_per_hari" type="number" min="0" value="{{ old('default_fidyah_per_hari', $defaultFidyahPerHari) }}" class="w-full rounded-xl border-gray-200 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 transition-all" required />
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1" for="default_fidyah_beras_per_hari">Default Fidyah Beras / Hari (Kg)</label>
                                <input id="default_fidyah_beras_per_hari" name="default_fidyah_beras_per_hari" type="number" step="0.01" min="0" value="{{ old('default_fidyah_beras_per_hari', $defaultFidyahBerasPerHari) }}" class="w-full rounded-xl border-gray-200 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 transition-all" required />
                            </div>
                        </div>

                        <hr class="border-gray-100" />

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1" for="public_refresh_interval_seconds">Interval Auto-Refresh Summary Publik (detik)</label>
                            <input id="public_refresh_interval_seconds" name="public_refresh_interval_seconds" type="number" min="0" max="60" value="{{ old('public_refresh_interval_seconds', $publicRefreshIntervalSeconds ?? 15) }}" class="w-full rounded-xl border-gray-200 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 transition-all" required />
                            <p class="mt-1 text-xs text-gray-500">0 = mati. Rekomendasi saat puncak: 15 (boleh 10–60).</p>
                        </div>

                        <div class="pt-2 flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                            <button type="submit" class="inline-flex justify-center items-center gap-2 rounded-xl bg-emerald-600 px-6 py-3 text-sm font-bold text-white shadow-md hover:bg-emerald-700 transition-all w-full sm:w-auto">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                                </svg>
                                Simpan Pengaturan
                            </button>
                            <a href="{{ route('dashboard') }}" class="px-4 py-3 sm:py-2 text-sm font-bold text-gray-500 hover:text-gray-700 transition-colors w-full sm:w-auto text-center border sm:border-0 border-gray-200 rounded-xl sm:rounded-none">Kembali ke Dashboard</a>
                        </div>

                        <p class="text-xs text-gray-400 text-center sm:text-left">
                            Tahun yang tersedia saat ini: {{ implode(', ', $years) }}
                        </p>
                    </form>
                </div>
            </div>

            {{-- New Period Section --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="bg-amber-50 px-6 py-4 border-b border-amber-100 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                    </svg>
                    <h3 class="font-bold text-amber-900">Mulai Periode Baru</h3>
                </div>
                <div class="p-6">
                    <p class="text-sm text-gray-600 leading-relaxed">
                        Aksi ini akan mengubah Tahun Aktif ke tahun baru dan menyiapkan default tahun baru (copy dari default tahun aktif saat ini).
                        <strong>Disarankan lakukan backup database terlebih dahulu.</strong>
                    </p>

                    <form method="POST" action="{{ route('internal.settings.period.startNew') }}" class="mt-5 space-y-5">
                        @csrf

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1" for="new_year">Tahun Baru</label>
                            <input id="new_year" name="new_year" type="number" min="2000" max="2100" value="{{ old('new_year', $activeYear + 1) }}" class="w-full rounded-xl border-gray-200 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 transition-all" required />
                            <p class="mt-1 text-xs text-gray-500">Harus lebih besar dari Tahun Aktif saat ini ({{ $activeYear }}).</p>
                        </div>

                        <label class="flex items-start gap-3 text-sm p-4 bg-amber-50 rounded-xl border border-amber-100">
                            <input type="checkbox" name="backup_confirmed" value="1" class="mt-0.5 rounded border-gray-300 text-emerald-600 shadow-sm focus:ring-emerald-500" @checked(old('backup_confirmed')) required />
                            <span class="text-gray-700">Saya sudah backup database / siap menanggung risiko perubahan tahun aktif.</span>
                        </label>

                        <div>
                            <button type="submit" class="inline-flex justify-center items-center gap-2 rounded-xl bg-red-600 px-5 py-3 text-sm font-bold text-white shadow-md hover:bg-red-700 transition-all w-full sm:w-auto">
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
