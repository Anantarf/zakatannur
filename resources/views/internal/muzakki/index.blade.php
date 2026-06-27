<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="space-y-1 text-center sm:text-left">
                <h2 class="ui-page-title">
                    <svg xmlns="http://www.w3.org/2000/svg" class="ui-page-title-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Data Muzakki
                </h2>
                <p class="ui-page-title-copy">Pantau profil, kontribusi, dan riwayat muzakki secara ringan.</p>
            </div>
            <div class="flex items-center justify-center sm:justify-end gap-3 w-full sm:w-auto">
                @if (auth()->check() && in_array(auth()->user()->role, ['admin', 'super_admin'], true))
                    <a href="{{ route('internal.muzakki.trash') }}" class="ui-btn ui-btn-secondary px-4 py-2 text-sm text-slate-500 hover:text-red-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Sampah
                    </a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-5 sm:py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
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
                            Mohon Perbaiki Kesalahan Berikut:
                        </div>
                        <ul class="list-disc pl-10 space-y-1 text-sm">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <x-ui-stat-card title="Total Muzakki" :value="number_format($totalMuzakki ?? 0, 0, ',', '.')" description="Kontak aktif di master data." />
                <x-ui-stat-card title="Pernah Transaksi" :value="number_format($activeMuzakki ?? 0, 0, ',', '.')" tone="info" description="Memiliki riwayat transaksi valid." />
                <x-ui-stat-card title="Hasil Pencarian" :value="number_format($muzakki->total(), 0, ',', '.')" tone="muted" description="{{ $q ? 'Filter: ' . $q : 'Menampilkan semua data.' }}" />
            </div>

            {{-- Table --}}
            <div class="ui-card overflow-hidden">
                <div class="border-b border-slate-200 bg-slate-50/50 p-4">
                    <form method="GET" action="{{ route('internal.muzakki.index') }}" class="flex w-full flex-col gap-2 sm:flex-row sm:items-center" x-data="{ submitTimeout: null }" @submit.prevent>
                        <input type="text" name="q" value="{{ $q }}" placeholder="Cari nama/alamat..." class="ui-input w-full sm:flex-1" @input="clearTimeout(submitTimeout); submitTimeout = setTimeout(() => $el.closest('form').submit(), 400)" @keydown.enter="$el.closest('form').submit()" />
                        <div class="flex w-full flex-none items-center gap-2 sm:w-auto">
                            <button type="submit" class="ui-btn ui-btn-secondary flex-1 sm:flex-none px-4 py-2.5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                Cari
                            </button>
                            @if($q)
                                <a href="{{ route('internal.muzakki.index') }}" class="ui-btn ui-btn-secondary flex-1 text-center sm:flex-none px-4 py-2.5" title="Reset Pencarian">
                                    Reset
                                </a>
                            @endif
                        </div>
                    </form>
                </div>
                <div class="space-y-3 p-4 md:hidden">
                    @if (count($muzakki) > 0)
                        @foreach ($muzakki as $m)
                            <article class="ui-mobile-card">
                                <div class="min-w-0">
                                    <div class="text-sm font-bold leading-tight text-slate-800">{!! \App\Support\Format::highlight($m->name, $q) !!}</div>
                                    <div class="mt-1 text-xs text-slate-500">{{ $m->phone ?? '-' }}</div>
                                    <div class="mt-2 text-sm leading-relaxed text-slate-600">{!! \App\Support\Format::highlight($m->address ?? '-', $q) !!}</div>
                                    <div class="mt-3 grid grid-cols-2 gap-2 text-xs">
                                        <div class="rounded-xl bg-brand-50 px-3 py-2 text-brand-800">
                                            <div class="font-bold">{{ (int) ($m->valid_transactions_count ?? 0) }} transaksi</div>
                                            <div class="text-brand-700">Riwayat valid</div>
                                        </div>
                                        <div class="rounded-xl bg-slate-50 px-3 py-2 text-slate-700">
                                            <div class="font-bold">{{ $m->last_transaction_at ? \Carbon\Carbon::parse($m->last_transaction_at)->timezone(config('zakat.timezone'))->translatedFormat('d M Y') : '-' }}</div>
                                            <div class="text-slate-500">Terakhir</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4 grid grid-cols-3 gap-2">
                                    <a class="ui-btn ui-btn-primary px-3 py-3 text-xs" href="{{ route('internal.muzakki.show', ['muzakki' => $m->id]) }}">
                                        Profil
                                    </a>
                                    <a class="ui-btn ui-btn-secondary px-3 py-3 text-xs text-blue-600 hover:text-blue-800" href="{{ route('internal.muzakki.edit', ['muzakki' => $m->id]) }}">
                                        Ubah
                                    </a>
                                    <button type="button" @click="$dispatch('open-modal', 'delete-muzakki-modal'); $dispatch('open-delete-modal', { id: {{ $m->id }}, name: '{{ addslashes($m->name) }}' })" class="ui-btn ui-btn-danger px-3 py-3 text-xs">
                                        Hapus
                                    </button>
                                </div>
                            </article>
                        @endforeach
                    @else
                        <div class="ui-empty-state">
                            <div class="flex flex-col items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-slate-200 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                                <span class="ui-empty-state-copy">
                                    {{ ($q ?? '') ? 'Data tidak ditemukan.' : 'Belum ada data muzakki.' }}
                                </span>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="hidden overflow-x-auto w-full md:block">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 bg-slate-50 text-left text-[11px] font-bold uppercase tracking-[0.14em] text-slate-600 sm:text-xs">
                                <th class="px-3 sm:px-5 py-3">Nama</th>
                                <th class="px-3 sm:px-5 py-3">No HP</th>
                                <th class="px-3 sm:px-5 py-3">Alamat</th>
                                <th class="px-3 sm:px-5 py-3">CRM Ringkas</th>
                                <th class="px-3 sm:px-5 py-3 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @if (count($muzakki) > 0)
                                @foreach ($muzakki as $m)
                                <tr class="transition-colors hover:bg-slate-50 border-b border-slate-100">
                                    <td class="px-3 sm:px-5 py-4 font-bold text-slate-800">{!! \App\Support\Format::highlight($m->name, $q) !!}</td>
                                    <td class="px-3 sm:px-5 py-4 font-medium text-slate-600">{{ $m->phone ?? '-' }}</td>
                                    <td class="px-3 sm:px-5 py-4 font-medium text-slate-600 text-sm">{!! \App\Support\Format::highlight($m->address ?? '-', $q) !!}</td>
                                    <td class="px-3 sm:px-5 py-3">
                                        <div class="space-y-1 text-xs">
                                            <div class="font-bold text-slate-800">{{ (int) ($m->valid_transactions_count ?? 0) }} transaksi</div>
                                            <div class="text-slate-500">{{ \App\Support\Format::rupiah((int) ($m->valid_total_uang ?? 0)) }} / {{ \App\Support\Format::kg((float) ($m->valid_total_beras ?? 0)) }}</div>
                                            <div class="text-slate-400">Terakhir: {{ $m->last_transaction_at ? \Carbon\Carbon::parse($m->last_transaction_at)->timezone(config('zakat.timezone'))->translatedFormat('d M Y') : '-' }}</div>
                                        </div>
                                    </td>
                                    <td class="px-3 sm:px-5 py-3 text-center whitespace-nowrap">
                                        <div class="flex items-center justify-center gap-2">
                                            <a class="ui-btn ui-btn-secondary px-3 py-2 text-xs border-brand-200 text-brand-700 hover:bg-brand-50" href="{{ route('internal.muzakki.show', ['muzakki' => $m->id]) }}">
                                                Profil
                                            </a>
                                            <a class="ui-btn ui-btn-secondary px-3 py-2 text-xs text-blue-600 hover:text-blue-800" href="{{ route('internal.muzakki.edit', ['muzakki' => $m->id]) }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                                Ubah
                                            </a>
                                            
                                            <button type="button" @click="$dispatch('open-modal', 'delete-muzakki-modal'); $dispatch('open-delete-modal', { id: {{ $m->id }}, name: '{{ addslashes($m->name) }}' })" class="ui-btn ui-btn-secondary px-3 py-2 text-xs border-red-200 text-red-600 hover:bg-red-50">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                                Hapus
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-slate-200 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                            </svg>
                                            <span class="ui-empty-state-copy">
                                                {{ ($q ?? '') ? 'Data tidak ditemukan.' : 'Belum ada data muzakki.' }}
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
                @if ($muzakki->hasPages())
                    <div class="border-t border-slate-200 px-5 py-3">
                        {{ $muzakki->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Modal Konfirmasi Hapus Muzakki -->
    <x-modal name="delete-muzakki-modal" focusable>
        <form method="POST" x-data="{ id: '', name: '' }" x-on:open-delete-modal.window="id = $event.detail.id; name = $event.detail.name; $el.action = '{{ url('/internal/muzakki') }}/' + id;" class="p-6">
            @csrf
            @method('DELETE')
            <h2 class="text-lg font-medium text-slate-900">
                Pindahkan Muzakki <span x-text="name" class="font-bold text-brand-700"></span> ke Tempat Sampah?
            </h2>
            <p class="mt-1 text-sm text-slate-600">
                Data muzakki akan dipindahkan ke folder sampah. <span class="font-bold">Data akan otomatis dihapus permanen dari sistem setelah 30 hari.</span>
            </p>
            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    Batal
                </x-secondary-button>
                <x-danger-button class="ml-3">
                    Ya, Pindahkan
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</x-app-layout>
