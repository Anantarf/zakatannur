<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <h2 class="ui-page-title">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 shrink-0 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                Sampah Transaksi
            </h2>
            <a href="{{ route('internal.transactions.index') }}" class="ui-header-link">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Kembali ke Riwayat
            </a>
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
                            Mohon Periksa:
                        </div>
                        <ul class="list-disc pl-10 space-y-1 text-sm">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            {{-- Trash Table --}}
            <div class="ui-card overflow-hidden">
                <div class="ui-card-header ui-card-header-slate">
                    <div class="ui-section-accent h-6 w-2"></div>
                    <h3 class="ui-card-header-title">Transaksi Terhapus</h3>
                </div>
                <div class="space-y-3 p-4 md:hidden">
                    @if (count($transactions) > 0)
                        @foreach ($transactions as $t)
                            <article class="ui-mobile-card">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <span class="inline-flex rounded-md bg-red-50 px-2 py-1 font-sans text-[11px] font-bold text-red-500">{!! \App\Support\Format::highlight($t->no_transaksi, $q) !!}</span>
                                        <div class="mt-2 text-sm font-semibold leading-tight text-slate-800">{!! \App\Support\Format::highlight($t->pembayar_nama, $q) !!}</div>
                                        @if($t->muzakki_total > 1)
                                            <div class="mt-1 text-[11px] text-slate-500">+ {{ $t->muzakki_total - 1 }} muzakki lainnya</div>
                                        @endif
                                    </div>
                                    <div class="shrink-0 text-right">
                                        <div class="text-[11px] font-semibold text-slate-500">{{ $t->deleted_at_formatted }}</div>
                                        @if($t->days_left !== null)
                                            <div class="mt-1 text-[10px] font-bold {{ $t->days_left <= 7 ? 'text-red-500' : 'text-slate-400' }}">
                                                {{ $t->days_left > 0 ? $t->days_left . ' hari lagi' : 'Hari ini' }}
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <div class="ui-mobile-card-muted space-y-3">
                                    <div class="flex items-start justify-between gap-3">
                                        <span class="text-[10px] font-semibold uppercase tracking-[0.12em] text-slate-400">Kategori</span>
                                        <div class="max-w-[65%]">
                                            <x-zakat-category-tags :categories="$t->categories_list" />
                                        </div>
                                    </div>
                                    <div class="flex items-start justify-between gap-3">
                                        <span class="text-[10px] font-semibold uppercase tracking-[0.12em] text-slate-400">Bentuk</span>
                                        <div class="flex max-w-[65%] flex-wrap justify-end gap-1">
                                            @foreach(explode(',', $t->methods_list ?? '') as $met)
                                                @php $met = trim($met); @endphp
                                                @if($met)
                                                    <span class="inline-flex items-center justify-center rounded px-2 py-0.5 text-[10px] font-semibold uppercase bg-amber-50 text-amber-700 border border-amber-100 whitespace-nowrap leading-tight text-center">{{ \App\Models\ZakatTransaction::METHOD_LABELS[$met] ?? strtoupper($met) }}</span>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                    <div class="flex items-start justify-between gap-3">
                                        <span class="text-[10px] font-semibold uppercase tracking-[0.12em] text-slate-400">Nominal</span>
                                        <div class="text-right">
                                            @if($t->total_uang > 0)
                                                <div class="flex items-center justify-end gap-1.5">
                                                    <div class="text-sm font-bold text-slate-800">{{ $t->total_uang_display }}</div>
                                                    @if($t->has_transfer)
                                                        <x-transfer-badge />
                                                    @else
                                                        <x-cash-badge />
                                                    @endif
                                                </div>
                                            @endif
                                            @if($t->total_beras > 0)
                                                <div class="mt-1 text-sm font-bold text-slate-800">{{ $t->total_beras_display }}</div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="flex items-start justify-between gap-3">
                                        <span class="text-[10px] font-semibold uppercase tracking-[0.12em] text-slate-400">Petugas</span>
                                        <div class="text-right">
                                            <div class="font-medium text-slate-700">{{ $t->petugas?->name ?? '-' }}</div>
                                            <span class="mt-1 inline-flex items-center justify-center rounded px-2 py-0.5 text-[10px] font-semibold uppercase bg-brand-50 text-brand-700 border border-brand-100 whitespace-nowrap leading-tight text-center">
                                                {{ $t->shift_label }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4 grid grid-cols-3 gap-2">
                                    <a class="ui-btn ui-btn-secondary px-3 py-3 text-xs" href="{{ route('internal.transactions.show', ['transaction' => $t->id]) }}">
                                        Lihat
                                    </a>
                                    <form method="POST" action="{{ route('internal.transactions.restore', ['transaction' => $t->id]) }}">
                                        @csrf
                                        <button type="submit" class="ui-btn ui-btn-primary w-full px-3 py-3 text-xs">
                                            Pulihkan
                                        </button>
                                    </form>
                                    @if(in_array(auth()->user()->role, ['admin', 'super_admin']))
                                        <button type="button" @click="$dispatch('open-modal', 'force-delete-modal'); $dispatch('open-force-delete-modal', { id: {{ $t->id }}, no: '{{ $t->no_transaksi }}' })" class="ui-btn ui-btn-danger w-full px-3 py-3 text-xs">
                                            Hapus
                                        </button>
                                    @else
                                        <div></div>
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    @else
                        <div class="ui-empty-state">
                            <div class="flex flex-col items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-slate-200 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span class="ui-empty-state-copy">
                                    {{ ($q ?? '') ? 'Data tidak ditemukan.' : 'Sampah kosong - semua transaksi aman.' }}
                                </span>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="hidden overflow-x-auto w-full md:block">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-100 bg-slate-50 text-left text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400 sm:text-xs">
                                <th class="px-3 py-3 sm:px-5">No. Transaksi</th>
                                <th class="px-3 py-3 sm:px-5">Dihapus</th>
                                <th class="px-3 py-3 sm:px-5">Pembayar</th>
                                <th class="px-2 py-3 text-center">Kategori</th>
                                <th class="px-2 py-3 text-center">Bentuk</th>
                                <th class="px-3 py-3 text-right sm:px-5">Total Nominal</th>
                                <th class="px-3 py-3 text-center sm:px-5">Petugas</th>
                                <th class="min-w-[120px] px-3 py-3 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @if (count($transactions) > 0)
                                @foreach ($transactions as $t)
                                <tr class="transition-colors hover:bg-slate-50">
                                    <td class="whitespace-nowrap px-3 py-3 sm:px-5">
                                        <span class="font-sans text-xs font-bold text-red-500 bg-red-50 px-2 py-1 rounded-md">{!! \App\Support\Format::highlight($t->no_transaksi, $q) !!}</span>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-3 text-[13px] text-slate-500 sm:px-5">
                                        <div class="leading-tight">{{ $t->deleted_at_formatted }}</div>
                                        @if($t->days_left !== null)
                                            <div class="mt-1 text-[11px] font-bold {{ $t->days_left <= 7 ? 'text-red-500' : 'text-slate-400' }}">
                                                {{ $t->days_left > 0 ? 'Sisa ' . $t->days_left . ' hari' : 'Hari ini' }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-3 py-3 sm:px-5">
                                        <div class="max-w-[180px] whitespace-normal break-words text-sm font-semibold leading-tight text-slate-700">{!! \App\Support\Format::highlight($t->pembayar_nama, $q) !!}</div>
                                        @if($t->muzakki_total > 1)
                                            <div class="mt-1 whitespace-nowrap text-[11px] text-slate-400">+ {{ $t->muzakki_total - 1 }} Muzakki Lainnya</div>
                                        @endif
                                    </td>
                                    <td class="px-2 py-3 text-center">
                                        <x-zakat-category-tags :categories="$t->categories_list" />
                                    </td>
                                    <td class="px-2 py-3">
                                        <div class="flex flex-wrap gap-1 justify-center max-w-[100px] mx-auto">
                                            @foreach(explode(',', $t->methods_list ?? '') as $met)
                                                @php $met = trim($met); @endphp
                                                @if($met)
                                                    <span class="inline-flex items-center justify-center rounded px-2 py-0.5 text-[11px] font-semibold uppercase bg-amber-50 text-amber-700 border border-amber-100 whitespace-nowrap leading-tight text-center">{{ \App\Models\ZakatTransaction::METHOD_LABELS[$met] ?? strtoupper($met) }}</span>
                                                @endif
                                            @endforeach
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-3 text-right sm:px-5">
                                        <div class="space-y-0.5">
                                            @if($t->total_uang > 0)
                                                <div class="flex items-center justify-end gap-1">
                                                    <div class="text-sm font-semibold text-slate-800">{{ $t->total_uang_display }}</div>
                                                    @if($t->has_transfer)
                                                        <x-transfer-badge />
                                                    @else
                                                        <x-cash-badge />
                                                    @endif
                                                </div>
                                            @endif
                                            @if($t->total_beras > 0)
                                                <div class="text-sm font-semibold text-slate-800">{{ $t->total_beras_display }}</div>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-3 text-center text-[13px] text-slate-500 sm:px-5">
                                        <div class="flex flex-col items-center gap-1">
                                            <span class="font-medium text-slate-700">{{ $t->petugas?->name ?? '-' }}</span>
                                            <span class="inline-flex items-center justify-center rounded px-2 py-0.5 text-[11px] font-bold uppercase bg-brand-50 text-brand-700 border border-brand-100 whitespace-nowrap leading-tight text-center">
                                                {{ $t->shift_label }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-3 text-center">
                                        <div class="flex items-center justify-center gap-1.5">
                                            <a class="ui-icon-button ui-icon-button-slate px-2" href="{{ route('internal.transactions.show', ['transaction' => $t->id]) }}" title="Lihat">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                                <span class="ui-table-action-label">Lihat</span>
                                            </a>
                                            <form method="POST" action="{{ route('internal.transactions.restore', ['transaction' => $t->id]) }}" class="inline">
                                                @csrf
                                                <button type="submit" class="ui-icon-button ui-icon-button-blue px-2" title="Pulihkan">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                                    </svg>
                                                    <span class="ui-table-action-label">Pulihkan</span>
                                                </button>
                                            </form>
                                            @if(in_array(auth()->user()->role, ['admin', 'super_admin']))
                                            <button type="button" @click="$dispatch('open-modal', 'force-delete-modal'); $dispatch('open-force-delete-modal', { id: {{ $t->id }}, no: '{{ $t->no_transaksi }}' })" class="ui-icon-button ui-icon-button-danger px-2" title="Hapus Permanen">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                                <span class="ui-table-action-label">Hapus</span>
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
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-slate-200 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            <span class="ui-empty-state-copy">
                                                {{ ($q ?? '') ? 'Data tidak ditemukan.' : 'Sampah kosong - semua transaksi aman.' }}
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                @if ($transactions->hasPages())
                    <div class="border-t border-slate-100 px-6 py-4">
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
            <h2 class="text-lg font-medium text-slate-900">
                Apakah Anda yakin ingin menghapus permanen transaksi <span x-text="no" class="font-bold font-sans text-red-600"></span>?
            </h2>
            <p class="mt-1 text-sm text-slate-600">
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
