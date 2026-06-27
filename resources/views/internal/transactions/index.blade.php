<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="space-y-1 text-center sm:text-left">
                <h2 class="ui-page-title">
                    <svg xmlns="http://www.w3.org/2000/svg" class="ui-page-title-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    Riwayat Transaksi
                </h2>
                <p class="ui-page-title-copy">Telusuri, filter, dan kelola transaksi tercatat.</p>
            </div>

            <div class="flex flex-col sm:flex-row items-center gap-2 w-full sm:w-auto">
                <a href="{{ route('internal.transactions.create') }}" class="ui-btn ui-btn-primary w-full px-4 py-3 sm:w-auto sm:py-2.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4" /></svg>
                    Input Baru
                </a>

                @if (auth()->check() && in_array(auth()->user()->role, ['admin', 'super_admin'], true))
                    <div class="flex items-center gap-1.5 w-full sm:w-auto">
                        <button type="button" @click="$dispatch('open-modal', 'export-daily-modal')" class="ui-btn ui-btn-secondary flex-1 px-3 py-2.5 text-xs text-brand-700 hover:text-brand-700 sm:flex-none sm:py-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                            Ekspor
                        </button>
                        <a href="{{ route('internal.transactions.trash') }}" class="ui-btn ui-btn-secondary flex-none px-3 py-2.5 text-slate-400 hover:text-red-600 sm:px-2.5 sm:py-2" title="Sampah">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-4 sm:py-6">
        <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
            @if (session('just_deleted_id'))
            <div x-data="{ show: true }" x-show="show" x-transition
                 class="flex items-center justify-between gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm">
                <span>Transaksi <strong>{{ session('just_deleted_no') }}</strong> dipindahkan ke sampah.</span>
                <div class="flex gap-2">
                    <form method="POST" action="{{ route('internal.transactions.restore', session('just_deleted_id')) }}" class="inline">
                        @csrf
                        <button type="submit" class="font-bold text-brand-700 hover:underline">Kembalikan</button>
                    </form>
                    <button @click="show = false" class="text-slate-400 hover:text-slate-600">Tutup</button>
                </div>
            </div>
            @endif

            @if ($errors->any())
                <x-form-errors />
            @endif
            
            @if ($canViewRisk && !empty($zakkyAnomalyInsight['message']))
                <x-zakky-insight
                    :tone="$zakkyAnomalyInsight['tone']"
                    :label="$zakkyAnomalyInsight['label']"
                    :message="$zakkyAnomalyInsight['message']"
                    :items="$zakkyAnomalyInsight['items'] ?? []"
                />
            @endif

            {{-- Transactions Table --}}
            <div class="ui-card overflow-hidden">
                <div class="ui-toolbar-soft xl:flex-row xl:items-start">
                    <div class="max-w-full space-y-1 xl:max-w-[260px] xl:flex-none">
                        <div class="ui-section-title">
                            <div class="ui-section-accent"></div>
                            <h3 class="font-semibold text-slate-800">Daftar Transaksi</h3>
                        </div>
                        <p class="text-sm leading-6 text-slate-500">Filter dan aksi cepat per transaksi.</p>
                    </div>

                    @include('internal.transactions.partials.history-filters')
                </div>



                <div class="space-y-3 px-4 pb-4 pt-4 md:hidden">
                    @forelse ($transactions as $t)
                        @include("internal.transactions.partials._mobile_card", ["t" => $t])
                    @empty
                        <div class="ui-empty-state">
                            <div class="flex flex-col items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-slate-200 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <span class="ui-empty-state-copy">
                                    {{ ($q || $year || $category) ? "Data tidak ditemukan." : "Belum ada transaksi ditemukan." }}
                                </span>
                            </div>
                        </div>
                    @endforelse
                </div>


                <div class="hidden overflow-x-auto w-full md:block">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-100 bg-slate-50 text-left text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400 sm:text-xs">
                                <th class="px-3 py-4 sm:px-5">No. Transaksi</th>
                                <th class="px-3 py-4 sm:px-5">Waktu</th>
                                <th class="px-3 py-4 sm:px-5">Pembayar</th>
                                <th class="px-2 py-4 text-center">Kategori</th>
                                <th class="px-2 py-4 text-center">Bentuk</th>
                                <th class="px-3 py-4 text-right sm:px-5">Total Nominal</th>
                                @if ($canViewRisk)
                                    <th class="px-2 py-4 text-center sm:px-4">Risiko</th>
                                @endif
                                <th class="px-3 py-4 text-center sm:px-5">Petugas</th>
                                <th class="w-[1%] pl-3 pr-6 py-4 text-center sm:pl-5 sm:pr-8">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100/80">
                            @forelse ($transactions as $t)
                                @include("internal.transactions.partials._desktop_row", ["t" => $t])
                            @empty
                                <tr>
                                    <td colspan="{{ $canViewRisk ? 9 : 8 }}" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-slate-200 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            <span class="ui-empty-state-copy">
                                                {{ ($q || $year || $category) ? "Data tidak ditemukan." : "Belum ada transaksi ditemukan." }}
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($transactions->hasPages())
                    <div class="border-t border-slate-100 px-5 py-3">
                        {{ $transactions->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Modal Konfirmasi Pindah Sampah -->
    <x-modal name="trash-modal" :show="$errors->has('deleted_reason')" focusable>
        <form method="POST" x-data="{ id: '', no: '' }" x-on:open-trash-modal.window="id = $event.detail.id; no = $event.detail.no; $el.action = '{{ url('/internal/transactions') }}/' + id + '/trash';" class="p-6">
            @csrf
            <h2 class="text-lg font-medium text-slate-900">
                Yakin ingin memindahkan transaksi <span x-text="no" class="font-bold font-sans text-red-600"></span> ke Sampah?
            </h2>
            <p class="mt-1 text-sm text-slate-600">
                Transaksi akan otomatis dihapus permanen dari sistem setelah 30 hari.
            </p>
            <div class="mt-4">
                <x-input-label for="deleted_reason" :value="'Alasan Penghapusan'" />
                <textarea
                    id="deleted_reason"
                    name="deleted_reason"
                    rows="3"
                    class="ui-textarea mt-1 w-full focus:border-red-500 focus:ring-red-500"
                    required
                >{{ old('deleted_reason') }}</textarea>
                <x-input-error :messages="$errors->get('deleted_reason')" class="mt-2" />
            </div>
            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    Batal
                </x-secondary-button>
                <x-danger-button class="ml-3">
                    Ya, Pindahkan ke Sampah
                </x-danger-button>
            </div>
        </form>
    </x-modal>

    <!-- Modal Akses Terbatas untuk Staf -->
    <x-modal name="restricted-modal" focusable maxWidth="sm">
        <div class="p-6 text-center">
            <div class="w-16 h-16 bg-amber-50 rounded-full flex items-center justify-center mx-auto mb-4 border border-amber-100">
                <svg class="h-8 w-8 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
            </div>
            <h2 class="text-lg font-bold text-slate-900 mb-2">Akses Terbatas</h2>
            <p class="text-sm text-slate-600 mb-6 leading-relaxed">
                <strong>Staf</strong> hanya dapat mengubah atau menghapus transaksi <strong>miliknya sendiri</strong> yang dibuat <strong>hari ini</strong>. Hubungi Admin atau Super Admin untuk kasus lain.
            </p>
            <div class="flex justify-center">
                <button type="button" x-on:click="$dispatch('close')" class="ui-btn ui-btn-secondary px-6 py-2.5">
                    Mengerti
                </button>
            </div>
        </div>
    </x-modal>

    <!-- Modal Export Harian -->
    <x-modal name="export-daily-modal" focusable maxWidth="sm">
        <form method="GET" action="{{ route('internal.rekap.export.daily') }}" class="p-6">
            <h2 class="text-lg font-semibold text-slate-900 flex items-center gap-2 mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-brand-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                Pilih Tanggal (Rekap Harian)
            </h2>
            <p class="mt-1 text-sm text-slate-600 mb-6">
                Silakan pilih tanggal transaksi yang ingin diekspor. Opsi yang tersedia hanya menyesuaikan dengan data transaksi tervalidasi di sistem.
            </p>

            <div class="mb-6">
                <x-input-label for="daily_date" :value="'Tanggal Transaksi'" class="mb-2" />
                @if(isset($availableDates) && count($availableDates) > 0)
                        <select id="daily_date" name="date" required class="ui-select w-full bg-white">
                            <option value="">-- Pilih Tanggal --</option>
                            @foreach ($availableDates as $rawDate => $formattedDate)
                                <option value="{{ $rawDate }}">{{ $formattedDate }}</option>
                            @endforeach
                        </select>
                @else
                    <div class="p-4 bg-slate-50 border border-slate-200 rounded-lg text-sm text-slate-500 not-italic text-center">
                        Belum ada transaksi valid yang tersedia untuk diekspor.
                    </div>
                @endif
            </div>

            <div class="flex flex-col sm:flex-row justify-end items-stretch sm:items-center gap-3">
                <x-secondary-button x-on:click="$dispatch('close')" class="justify-center">Batal</x-secondary-button>
                @if(isset($availableDates) && count($availableDates) > 0)
                    <button type="submit" class="ui-btn ui-btn-primary" x-on:click="setTimeout(() => $dispatch('close'), 1500)">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                        Download Excel
                    </button>
                @endif
            </div>
        </form>
    </x-modal>

    <!-- Modal Export Tahunan -->
    <x-modal name="export-yearly-modal" focusable maxWidth="sm">
        <form method="GET" action="{{ route('internal.rekap.export.yearly') }}" class="p-6">
            <h2 class="text-lg font-semibold text-slate-900 flex items-center gap-2 mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                Pilih Tahun (Rekap Tahunan)
            </h2>
            <p class="mt-1 text-sm text-slate-600 mb-6">
                Silakan pilih tahun periode Zakat. Sistem akan mengakumulasi seluruh transaksi per hari pada tahun tersebut.
            </p>

            <div class="mb-6">
                <x-input-label for="yearly_year" :value="'Tahun Zakat'" class="mb-2" />
                @if(isset($availableYears) && count($availableYears) > 0)
                        <select id="yearly_year" name="year" required class="ui-select w-full bg-white focus:border-blue-500 focus:ring-blue-500">
                            <option value="">-- Pilih Tahun --</option>
                            @foreach ($availableYears as $optYear)
                                <option value="{{ $optYear }}">{{ $optYear }}</option>
                            @endforeach
                        </select>
                @else
                    <div class="p-4 bg-slate-50 border border-slate-200 rounded-lg text-sm text-slate-500 not-italic text-center">
                        Belum ada transaksi valid yang tersedia untuk diekspor.
                    </div>
                @endif
            </div>

            <div class="flex flex-col sm:flex-row justify-end items-stretch sm:items-center gap-3">
                <x-secondary-button x-on:click="$dispatch('close')" class="justify-center">Batal</x-secondary-button>
                @if(isset($availableYears) && count($availableYears) > 0)
                    <button type="submit" class="ui-btn ui-btn-accent" x-on:click="setTimeout(() => $dispatch('close'), 1500)">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                        Download Excel
                    </button>
                @endif
            </div>
        </form>
    </x-modal>

</x-app-layout>
