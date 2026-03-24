<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <h2 class="font-bold text-xl sm:text-2xl text-emerald-800 leading-tight flex items-center justify-center sm:justify-start gap-2 text-center sm:text-left">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                Riwayat Transaksi
            </h2>

            <div class="flex flex-col sm:flex-row items-center gap-2 w-full sm:w-auto">
                <a href="{{ route('internal.transactions.create') }}" class="inline-flex justify-center items-center gap-2 rounded-xl bg-emerald-600 px-4 py-3 sm:py-2 text-sm font-bold text-white shadow-lg shadow-emerald-200 hover:bg-emerald-700 transition-all w-full sm:w-auto">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4" /></svg>
                    Input Baru
                </a>

                @if (auth()->check() && in_array(auth()->user()->role, ['admin', 'super_admin'], true))
                    <div class="flex items-center gap-1.5 w-full sm:w-auto">
                        <button type="button" x-data x-on:click="$dispatch('open-modal', 'export-daily-modal')" class="flex-1 sm:flex-none inline-flex justify-center items-center gap-1.5 rounded-xl bg-white border border-gray-200 px-3 py-2.5 sm:py-2 text-xs font-bold text-emerald-700 shadow-sm hover:bg-emerald-50 transition-all">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                            Export
                        </button>
                        <a href="{{ route('internal.transactions.trash') }}" class="flex-none p-2.5 sm:p-2 rounded-xl bg-white border border-gray-200 text-gray-400 hover:text-red-600 hover:bg-red-50 transition-all" title="Sampah">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-6 sm:py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if ($errors->any())
                <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-red-900 shadow-sm">
                    <div class="flex items-center gap-2 font-bold text-red-700 mb-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                        Mohon Perbaiki Kesalahan Berikut:
                    </div>
                    <ul class="list-disc pl-10 space-y-1 text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif


            {{-- Transactions Table --}}
            <div class="bg-white rounded-2xl shadow-md border border-gray-100 overflow-hidden">
                <div class="px-4 sm:px-6 py-4 border-b border-gray-50 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                    <div class="flex items-center gap-2">
                        <div class="w-2 h-6 bg-emerald-500 rounded-full"></div>
                        <h3 class="font-semibold text-gray-800">Daftar Transaksi</h3>
                    </div>

                    <form method="GET" action="{{ route('internal.transactions.index') }}" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 mt-3 lg:mt-0 w-full lg:w-auto">
                        <input type="text" name="q" value="{{ $q ?? '' }}" placeholder="Cari..." class="rounded-xl border-gray-200 bg-gray-50 px-4 py-2 text-sm font-medium text-gray-600 focus:border-emerald-500 focus:ring-emerald-500 w-full sm:w-auto sm:min-w-[230px]" />
                        
                        <div class="relative w-full sm:w-auto flex-shrink-0" style="min-width: 160px;">
                            <select name="category" onchange="this.form.submit()" class="appearance-none rounded-xl border-gray-200 bg-gray-50 pl-4 pr-10 py-2 text-sm font-medium text-gray-600 focus:border-emerald-500 focus:ring-emerald-500 cursor-pointer w-full">
                                <option value="">Semua Kategori</option>
                                @foreach ($categories ?? [] as $c)
                                    <option value="{{ $c }}" @selected(($category ?? '') === $c)>{{ \App\Models\ZakatTransaction::CATEGORY_LABELS[$c] ?? strtoupper($c) }}</option>
                                @endforeach
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-400">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </div>
                        </div>

                        <div class="relative w-full sm:w-auto flex-1">
                            <select name="year" onchange="this.form.submit()" class="appearance-none rounded-xl border-gray-200 bg-gray-50 pl-4 pr-10 py-2 text-sm font-medium text-gray-600 focus:border-emerald-500 focus:ring-emerald-500 cursor-pointer w-full">
                                <option value="">Semua Tahun</option>
                                @foreach ($years ?? [] as $y)
                                    <option value="{{ $y }}" @selected((string) ($year ?? '') === (string) $y)>{{ $y }}</option>
                                @endforeach
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-400">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </div>
                        </div>

                        <button type="submit" class="p-2 bg-gray-800 text-white rounded-xl hover:bg-gray-900 transition-all w-full sm:w-auto flex justify-center items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </button>
                    </form>
                </div>
                <div class="overflow-x-auto w-full">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 text-left text-[11px] sm:text-xs font-bold text-gray-400 uppercase tracking-widest border-b border-gray-100">
                                <th class="px-2 sm:px-6 py-4">No. Transaksi</th>
                                <th class="px-2 sm:px-6 py-4">Waktu</th>
                                <th class="px-2 sm:px-6 py-4">Pembayar</th>
                                <th class="px-2 py-4 text-center">Kategori</th>
                                <th class="px-2 py-4 text-center">Bentuk</th>
                                <th class="px-2 sm:px-6 py-4 text-right">Total Nominal</th>
                                <th class="px-2 sm:px-6 py-4 text-center">Petugas</th>
                                <th class="px-4 py-4 text-center min-w-[160px]">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @if (count($transactions) > 0)
                                @foreach ($transactions as $t)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-3 py-4 whitespace-nowrap">
                                        <span class="font-mono text-xs font-semibold text-blue-600 bg-blue-50 px-2 py-1 rounded-md">{!! \App\Support\Format::highlight($t->no_transaksi, $q) !!}</span>
                                    </td>
                                    <td class="px-3 py-4 text-gray-500 text-[13px] whitespace-nowrap">
                                        @if ($t->waktu_terima)
                                            {{ $t->waktu_terima->timezone('Asia/Jakarta')->format('d/m/Y H:i') }}
                                        @else
                                            {{ $t->created_at->timezone('Asia/Jakarta')->format('d/m/Y H:i') }}
                                        @endif
                                    </td>
                                    <td class="px-3 py-4">
                                        <div class="text-sm font-semibold text-gray-700 max-w-[180px] break-words whitespace-normal leading-tight">{!! \App\Support\Format::highlight($t->pembayar_nama, $q) !!}</div>
                                        @if($t->muzakki_total > 1)
                                            <div class="text-[11px] text-gray-400 mt-1 whitespace-nowrap">+ {{ $t->muzakki_total - 1 }} Muzakki Lainnya</div>
                                        @endif
                                    </td>
                                    <td class="px-2 py-4 text-center">
                                        <x-zakat-category-tags :categories="$t->categories_list" />
                                    </td>
                                    <td class="px-2 py-4">
                                        <div class="flex flex-wrap gap-1 justify-center max-w-[90px] mx-auto">
                                            @foreach(explode(',', $t->methods_list ?? '') as $met)
                                                @php $met = trim($met); @endphp
                                                @if($met)
                                                    <span class="inline-flex items-center justify-center rounded px-2 py-0.5 text-[11px] font-semibold uppercase bg-amber-50 text-amber-700 border border-amber-100 whitespace-nowrap leading-tight text-center">{{ \App\Models\ZakatTransaction::METHOD_LABELS[$met] ?? strtoupper($met) }}</span>
                                                @endif
                                            @endforeach
                                        </div>
                                    </td>
                                    <td class="px-3 py-4 text-right whitespace-nowrap">
                                        <div class="space-y-0.5">
                                            @if($t->total_uang > 0)
                                                <div class="font-semibold text-gray-800 text-sm flex items-center justify-end gap-1">
                                                    {{ $t->total_uang_display }}
                                                    @if($t->has_transfer)
                                                        <span class="text-[9px] font-black text-emerald-600 bg-emerald-50 px-1 py-0.5 rounded border border-emerald-100 italic">TF</span>
                                                    @endif
                                                </div>
                                            @endif
                                            @if($t->total_beras > 0)
                                                <div class="font-semibold text-gray-800 text-sm">{{ $t->total_beras_display }}</div>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-3 py-4 text-center text-gray-500 text-[13px] whitespace-nowrap">
                                        <div class="flex flex-col items-center gap-1 text-center">
                                            <span class="font-medium text-gray-700">{{ $t->petugas?->name ?? '-' }}</span>
                                            <span class="inline-flex items-center justify-center rounded px-2 py-0.5 text-[11px] font-bold uppercase bg-emerald-50 text-emerald-700 border border-emerald-100 whitespace-nowrap leading-tight text-center">
                                                {{ $t->shift_label }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center whitespace-nowrap">
                                        @php
                                            $canModify = auth()->user()->role !== 'staff' || 
                                                        ($t->petugas_id === auth()->id() && $t->created_at->isToday());
                                        @endphp

                                        <div class="flex items-center justify-center gap-2">
                                            <a class="inline-flex items-center justify-center p-2 rounded-lg text-gray-500 hover:text-gray-700 hover:bg-gray-100 transition-colors" href="{{ route('internal.transactions.show', ['transaction' => $t->id]) }}" title="Lihat">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                            </a>
                                            
                                            @if($canModify)
                                                <a class="inline-flex items-center justify-center p-2 rounded-lg text-amber-600 hover:text-amber-800 hover:bg-amber-50 transition-colors" href="{{ route('internal.transactions.edit', ['transaction' => $t->id]) }}" title="Edit">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                                </a>
                                            @else
                                                <button type="button" x-data x-on:click="$dispatch('open-modal', 'restricted-modal')" class="inline-flex items-center justify-center p-2 rounded-lg text-gray-300 cursor-not-allowed" title="Edit Terbatas">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                                </button>
                                            @endif

                                            <a class="inline-flex items-center justify-center p-2 rounded-lg text-blue-600 hover:text-blue-800 hover:bg-blue-50 transition-colors" href="{{ route('internal.transactions.receipt', ['transaction' => $t->id]) }}" target="_blank" rel="noopener" title="Cetak Tanda Terima">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                                            </a>

                                            @if($canModify)
                                                <button type="button" x-data x-on:click="$dispatch('open-modal', 'trash-modal'); $dispatch('open-trash-modal', { id: {{ $t->id }}, no: '{{ $t->no_transaksi }}' })" class="inline-flex items-center justify-center p-2 rounded-lg text-red-500 hover:text-red-700 hover:bg-red-50 transition-colors" title="Hapus">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            @else
                                                <button type="button" x-data x-on:click="$dispatch('open-modal', 'restricted-modal')" class="inline-flex items-center justify-center p-2 rounded-lg text-gray-300 cursor-not-allowed" title="Hapus Terbatas">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="8" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-gray-200 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            <span class="text-sm font-medium text-gray-400">
                                                {{ ($q || $year || $category) ? 'Data tidak ditemukan.' : 'Belum ada transaksi ditemukan.' }}
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                @if ($transactions->hasPages())
                    <div class="px-6 py-4 border-t border-gray-50">
                        {{ $transactions->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Modal Konfirmasi Pindah Sampah -->
    <x-modal name="trash-modal" :show="$errors->deletion->isNotEmpty()" focusable>
        <form method="POST" x-data="{ id: '', no: '' }" x-on:open-trash-modal.window="id = $event.detail.id; no = $event.detail.no; $el.action = '{{ url('/internal/transactions') }}/' + id + '/trash';" class="p-6">
            @csrf
            <h2 class="text-lg font-medium text-gray-900">
                Yakin ingin memindahkan transaksi <span x-text="no" class="font-bold font-mono text-red-600"></span> ke Sampah?
            </h2>
            <p class="mt-1 text-sm text-gray-600">
                Transaksi akan otomatis dihapus permanen dari sistem setelah 30 hari.
            </p>
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
            <h2 class="text-lg font-bold text-gray-900 mb-2">Akses Terbatas</h2>
            <p class="text-sm text-gray-600 mb-6 leading-relaxed">
                Maaf, sesuai regulasi, **Staf** hanya diperbolehkan mengubah atau menghapus transaksi **milik sendiri** yang dibuat pada **hari yang sama**.
                <br><br>
                Silakan hubungi Admin atau Super Admin untuk bantuan lebih lanjut.
            </p>
            <div class="flex justify-center">
                <button type="button" x-on:click="$dispatch('close')" class="px-6 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl font-bold text-sm transition-all active:scale-[0.98]">
                    Mengerti
                </button>
            </div>
        </div>
    </x-modal>

    <!-- Modal Export Harian -->
    <x-modal name="export-daily-modal" focusable maxWidth="sm">
        <form method="GET" action="{{ route('internal.rekap.export.daily') }}" class="p-6">
            <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2 mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                Pilih Tanggal (Rekap Harian)
            </h2>
            <p class="mt-1 text-sm text-gray-600 mb-6">
                Silakan pilih tanggal transaksi yang ingin diekspor. Opsi yang tersedia hanya menyesuaikan dengan data transaksi tervalidasi di sistem.
            </p>

            <div class="mb-6">
                <x-input-label for="daily_date" :value="'Tanggal Transaksi'" class="mb-2" />
                @if(isset($availableDates) && count($availableDates) > 0)
                        <select id="daily_date" name="date" required class="border-gray-300 focus:border-emerald-500 focus:ring-emerald-500 rounded-lg shadow-sm w-full py-2.5 px-4 text-sm font-medium text-gray-700 cursor-pointer">
                            <option value="">-- Pilih Tanggal --</option>
                            @foreach ($availableDates as $rawDate => $formattedDate)
                                <option value="{{ $rawDate }}">{{ $formattedDate }}</option>
                            @endforeach
                        </select>
                @else
                    <div class="p-4 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-500 italic text-center">
                        Belum ada transaksi valid yang tersedia untuk diekspor.
                    </div>
                @endif
            </div>

            <div class="flex flex-col sm:flex-row justify-end items-stretch sm:items-center gap-3">
                <x-secondary-button x-on:click="$dispatch('close')" class="justify-center">Batal</x-secondary-button>
                @if(isset($availableDates) && count($availableDates) > 0)
                    <button type="submit" class="inline-flex justify-center items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-md hover:bg-emerald-700 transition-all" x-on:click="setTimeout(() => $dispatch('close'), 1500)">
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
            <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2 mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                Pilih Tahun (Rekap Tahunan)
            </h2>
            <p class="mt-1 text-sm text-gray-600 mb-6">
                Silakan pilih tahun periode Zakat. Sistem akan mengakumulasi seluruh transaksi per hari pada tahun tersebut.
            </p>

            <div class="mb-6">
                <x-input-label for="yearly_year" :value="'Tahun Zakat'" class="mb-2" />
                @if(isset($availableYears) && count($availableYears) > 0)
                        <select id="yearly_year" name="year" required class="border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-lg shadow-sm w-full py-2.5 px-4 text-sm font-medium text-gray-700 cursor-pointer">
                            <option value="">-- Pilih Tahun --</option>
                            @foreach ($availableYears as $optYear)
                                <option value="{{ $optYear }}">{{ $optYear }}</option>
                            @endforeach
                        </select>
                @else
                    <div class="p-4 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-500 italic text-center">
                        Belum ada transaksi valid yang tersedia untuk diekspor.
                    </div>
                @endif
            </div>

            <div class="flex flex-col sm:flex-row justify-end items-stretch sm:items-center gap-3">
                <x-secondary-button x-on:click="$dispatch('close')" class="justify-center">Batal</x-secondary-button>
                @if(isset($availableYears) && count($availableYears) > 0)
                    <button type="submit" class="inline-flex justify-center items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-md hover:bg-blue-700 transition-all" x-on:click="setTimeout(() => $dispatch('close'), 1500)">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                        Download Excel
                    </button>
                @endif
            </div>
        </form>
    </x-modal>
</x-app-layout>
