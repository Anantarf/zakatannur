<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <h2 class="font-bold text-xl sm:text-2xl text-emerald-800 leading-tight flex items-center justify-center sm:justify-start gap-2 text-center sm:text-left">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-red-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                Sampah Transaksi
            </h2>
            <a href="{{ route('internal.transactions.index') }}" class="inline-flex justify-center items-center gap-2 rounded-xl bg-white border border-gray-100 px-4 py-3 sm:py-2 text-sm font-bold text-gray-500 hover:text-emerald-700 hover:bg-emerald-50 transition-all w-full sm:w-auto shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Kembali ke Riwayat
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            {{-- Warning Banner --}}
            <div class="flex items-center gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-800 shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-amber-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                </svg>
                <div>
                    <p class="text-sm font-bold">Transaksi dihapus sementara akan dihapus permanen secara otomatis setelah <span class="underline">30 hari</span>.</p>
                    <p class="text-xs mt-0.5 font-medium text-amber-700">Anda masih dapat memulihkan transaksi sebelum batas waktu tersebut.</p>
                </div>
            </div>

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
                        Mohon Periksa:
                    </div>
                    <ul class="list-disc pl-10 space-y-1 text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Trash Table --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-50 flex items-center gap-2">
                    <div class="w-2 h-6 bg-red-400 rounded-full"></div>
                    <h3 class="font-bold text-gray-800">Transaksi Terhapus</h3>
                </div>
                <div class="overflow-x-auto w-full">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider border-b border-gray-100">
                                <th class="px-6 py-4">No. Transaksi</th>
                                <th class="px-6 py-4">Dihapus</th>
                                <th class="px-6 py-4">Pembayar</th>
                                <th class="px-4 py-4 text-center">Kategori</th>
                                <th class="px-4 py-4 text-center">Bentuk</th>
                                <th class="px-6 py-4 text-right">Total Nominal</th>
                                <th class="px-6 py-4 text-center">Petugas</th>
                                <th class="px-6 py-4 text-center min-w-[150px]">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @if (count($transactions) > 0)
                                @foreach ($transactions as $t)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="font-mono text-xs font-bold text-red-500 bg-red-50 px-2 py-1 rounded-md">{!! \App\Support\Format::highlight($t->no_transaksi, $q) !!}</span>
                                    </td>
                                    <td class="px-6 py-4 text-gray-500 text-xs whitespace-nowrap">
                                        <div>{{ $t->deleted_at_formatted }}</div>
                                        @if($t->days_left !== null)
                                            <div class="mt-0.5 text-[10px] font-bold {{ $t->days_left <= 7 ? 'text-red-500' : 'text-gray-400' }}">
                                                {{ $t->days_left > 0 ? 'Hapus permanen dalam ' . $t->days_left . ' hari' : 'Akan dihapus permanen hari ini' }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-[13px] font-semibold text-gray-700">{!! \App\Support\Format::highlight(\Illuminate\Support\Str::limit($t->pembayar_nama, 20), $q) !!}</div>
                                        @if($t->muzakki_total > 1)
                                            <div class="text-[10px] text-gray-400 mt-0.5 font-normal">+ {{ $t->muzakki_total - 1 }} Muzakki Lainnya</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4">
                                        <x-zakat-category-tags :categories="$t->categories_list" />
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="flex flex-wrap gap-1 justify-center max-w-[100px] mx-auto">
                                            @foreach(explode(',', $t->methods_list ?? '') as $met)
                                                @php $met = trim($met); @endphp
                                                @if($met)
                                                    <span class="inline-flex items-center justify-center rounded px-2 py-0.5 text-[9px] font-semibold uppercase bg-amber-50 text-amber-700 border border-amber-100 whitespace-nowrap leading-tight text-center">{{ \App\Models\ZakatTransaction::METHOD_LABELS[$met] ?? strtoupper($met) }}</span>
                                                @endif
                                            @endforeach
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="space-y-1">
                                            @if($t->total_uang > 0)
                                                <div class="flex items-center justify-end gap-1.5">
                                                    <div class="font-bold text-gray-800 text-xs">{{ $t->total_uang_display }}</div>
                                                    @if($t->has_transfer)
                                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-black bg-emerald-100 text-emerald-700 border border-emerald-200 uppercase">TF</span>
                                                    @endif
                                                </div>
                                            @endif
                                            @if($t->total_beras > 0)
                                                <div class="font-bold text-gray-800 text-xs">{{ $t->total_beras_display }}</div>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center text-gray-500 text-xs whitespace-nowrap">
                                        <div class="flex flex-col items-center gap-1">
                                            <span>{{ $t->petugas?->name ?? '-' }}</span>
                                            <span class="inline-flex items-center justify-center rounded px-2 py-0.5 text-[9px] font-bold uppercase bg-emerald-50 text-emerald-700 border border-emerald-100 whitespace-nowrap leading-tight text-center">
                                                {{ $t->shift_label }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center whitespace-nowrap">
                                        <div class="flex items-center justify-center gap-2">
                                            <a class="inline-flex items-center justify-center p-2 rounded-lg text-gray-500 hover:text-gray-700 hover:bg-gray-100 transition-colors" href="{{ route('internal.transactions.show', ['transaction' => $t->id]) }}" title="Lihat">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                            </a>
                                            <form method="POST" action="{{ route('internal.transactions.restore', ['transactionId' => $t->id]) }}">
                                                @csrf
                                                <button type="submit" class="inline-flex items-center justify-center p-2 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-600 hover:text-white transition-all shadow-sm border border-emerald-100" title="Pulihkan">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                                    </svg>
                                                </button>
                                            </form>
                                            @if(in_array(auth()->user()->role, ['admin', 'super_admin']))
                                            <button type="button" x-data x-on:click="$dispatch('open-modal', 'force-delete-modal'); $dispatch('open-force-delete-modal', { id: {{ $t->id }}, no: '{{ $t->no_transaksi }}' })" class="inline-flex items-center justify-center p-2 rounded-lg bg-red-50 text-red-600 hover:bg-red-600 hover:text-white transition-all shadow-sm border border-red-100" title="Hapus Permanen">
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
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            <span class="text-sm font-medium text-gray-400">
                                                {{ ($q ?? '') ? 'Data tidak ditemukan.' : 'Sampah kosong — semua transaksi aman.' }}
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

    <!-- Modal Konfirmasi Hapus Permanen -->
    <x-modal name="force-delete-modal" :show="$errors->forceDeletion->isNotEmpty()" focusable>
        <form method="POST" x-data="{ id: '', no: '' }" x-on:open-force-delete-modal.window="id = $event.detail.id; no = $event.detail.no; $el.action = '{{ url('/internal/transactions') }}/' + id + '/force-delete';" class="p-6">
            @csrf
            @method('delete')
            <h2 class="text-lg font-medium text-gray-900">
                Apakah Anda yakin ingin menghapus permanen transaksi <span x-text="no" class="font-bold font-mono text-red-600"></span>?
            </h2>
            <p class="mt-1 text-sm text-gray-600">
                Data yang dihapus permanen tidak akan bisa dipulihkan kembali dari sistem. Harap berhati-hati.
            </p>
            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    Batal
                </x-secondary-button>
                <x-danger-button class="ml-3">
                    Ya, Hapus Permanen
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</x-app-layout>
