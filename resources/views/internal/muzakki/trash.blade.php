<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <h2 class="ui-page-title">
                <svg xmlns="http://www.w3.org/2000/svg" class="ui-page-title-icon text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                Sampah Muzakki
            </h2>
            <a href="{{ route('internal.muzakki.index') }}" class="ui-header-link">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Kembali ke Daftar Muzakki
            </a>
        </div>
    </x-slot>

    <div class="py-4 sm:py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4 sm:space-y-5">
            <div class="ui-alert ui-alert-warning">
                <svg xmlns="http://www.w3.org/2000/svg" class="ui-alert-icon text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                </svg>
                <div>
                    <p class="text-sm font-bold">Data muzakki di sini akan dihapus permanen secara otomatis setelah <span class="underline">30 hari</span>.</p>
                    <p class="text-xs mt-0.5 font-medium text-amber-700">Pastikan muzakki tidak memiliki riwayat transaksi sebelum dihapus permanen.</p>
                </div>
            </div>

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

            {{-- Table --}}
            <div class="ui-card overflow-hidden">
                <div class="ui-toolbar-soft">
                    <div class="flex items-center gap-2">
                        <div class="ui-section-accent h-6 w-2"></div>
                        <h3 class="ui-section-title">Muzakki terhapus</h3>
                    </div>

                    <form method="GET" action="{{ route('internal.muzakki.trash') }}" class="flex w-full flex-col items-stretch gap-2 sm:w-auto sm:flex-row sm:items-center">
                        <input type="text" name="q" value="{{ $q }}" placeholder="Cari nama atau alamat..." class="ui-input w-full sm:min-w-[200px]" />
                        <div class="flex items-center justify-end gap-2 shrink-0">
                            <button type="submit" class="ui-btn ui-btn-secondary px-3 py-2.5 text-slate-600">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </button>
                            @if($q)
                                <a href="{{ route('internal.muzakki.trash') }}" class="ui-icon-button ui-icon-button-danger" title="Reset pencarian">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </a>
                            @endif
                        </div>
                    </form>
                </div>

                {{-- Mobile --}}
                <div class="space-y-3 p-4 md:hidden">
                    @forelse ($muzakki as $m)
                        <article class="ui-mobile-card">
                            <div class="flex items-start justify-between gap-3">
                                <div class="space-y-1">
                                    <h4 class="text-sm font-bold text-slate-900">{!! \App\Support\Format::highlight($m->name, $q) !!}</h4>
                                    <p class="text-xs font-medium text-slate-500">No HP:{{ $m->phone ?? '-' }}</p>
                                </div>
                                @if($m->days_left !== null)
                                    <span class="inline-flex items-center rounded-md px-2 py-1 text-[11px] font-bold {{ $m->days_left <= 7 ? 'bg-red-50 text-red-600' : 'bg-slate-50 text-slate-600' }}">
                                        {{ $m->days_left > 0 ? $m->days_left . ' hari lagi' : 'Dihapus hari ini' }}
                                    </span>
                                @endif
                            </div>

                            <div class="ui-mobile-meta-grid">
                                <div class="ui-mobile-meta-item col-span-2">
                                    <p class="ui-mobile-meta-label">Alamat</p>
                                    <p class="mt-1 text-sm leading-6 text-slate-600">{!! \App\Support\Format::highlight($m->address ?? '-', $q) !!}</p>
                                </div>
                                <div class="ui-mobile-meta-item">
                                    <p class="ui-mobile-meta-label">Dihapus pada</p>
                                    <p class="ui-mobile-meta-value">{{ $m->deleted_at_formatted }}</p>
                                </div>
                                <div class="ui-mobile-meta-item">
                                    <p class="ui-mobile-meta-label">Status</p>
                                    <p class="ui-mobile-meta-value">
                                        {{ $m->days_left === null ? 'Belum diketahui' : ($m->days_left > 0 ? 'Akan dihapus otomatis' : 'Dihapus hari ini') }}
                                    </p>
                                </div>
                            </div>

                            <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <form method="POST" action="{{ route('internal.muzakki.restore', $m->id) }}">
                                    @csrf
                                    <button type="submit" class="ui-btn ui-btn-primary w-full px-4 py-3">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                        Pulihkan
                                    </button>
                                </form>

                                <button type="button" @click="$dispatch('open-modal', 'force-delete-muzakki-modal'); $dispatch('open-force-delete-modal', { id: {{ $m->id }}, name: '{{ addslashes($m->name) }}' })" class="ui-btn ui-btn-danger w-full px-4 py-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                    Hapus permanen
                                </button>
                            </div>
                        </article>
                    @empty
                        <div class="ui-empty-state">
                            <div class="flex flex-col items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="mb-2 h-10 w-10 text-slate-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                                <span class="ui-empty-state-copy">
                                    {{ ($q ?? '') ? 'Tidak ada hasil yang cocok.' : 'Sampah kosong — semua muzakki aman.' }}
                                </span>
                            </div>
                        </div>
                    @endforelse
                </div>

                {{-- Desktop --}}
                <div class="hidden overflow-x-auto w-full rounded-2xl border border-slate-200 md:block">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="ui-table-header">
                                <th class="px-6 py-4">Nama</th>
                                <th class="px-6 py-4">Alamat</th>
                                <th class="px-6 py-4 text-center">Dihapus pada</th>
                                <th class="px-6 py-4 text-center">Sisa waktu</th>
                                <th class="px-6 py-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($muzakki as $m)
                                <tr class="transition-colors hover:bg-slate-50">
                                    <td class="px-6 py-4 font-bold text-slate-800">
                                        {!! \App\Support\Format::highlight($m->name, $q) !!}
                                        <div class="text-[10px] text-slate-400 font-medium">No HP:{{ $m->phone ?? '-' }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-slate-500 text-xs">{!! \App\Support\Format::highlight($m->address ?? '-', $q) !!}</td>
                                    <td class="px-6 py-4 text-center text-slate-500 text-xs whitespace-nowrap">
                                        {{ $m->deleted_at_formatted }}
                                    </td>
                                    <td class="px-6 py-4 text-center text-xs whitespace-nowrap">
                                        @if($m->days_left !== null)
                                            <span class="inline-flex items-center px-2 py-1 rounded-md font-bold {{ $m->days_left <= 7 ? 'bg-red-50 text-red-600' : 'bg-slate-50 text-slate-600' }}">
                                                {{ $m->days_left > 0 ? $m->days_left . ' hari lagi' : 'Dihapus hari ini' }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-center whitespace-nowrap">
                                        <div class="flex items-center justify-center gap-2">
                                            <form method="POST" action="{{ route('internal.muzakki.restore', $m->id) }}">
                                                @csrf
                                                <button type="submit" class="ui-btn ui-btn-secondary px-2 py-1.5 text-slate-500 hover:text-blue-600 hover:bg-blue-50" title="Pulihkan">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                                    </svg>
                                                </button>
                                            </form>

                                            <button type="button" @click="$dispatch('open-modal', 'force-delete-muzakki-modal'); $dispatch('open-force-delete-modal', { id: {{ $m->id }}, name: '{{ addslashes($m->name) }}' })" class="ui-btn ui-btn-secondary px-2 py-1.5 text-slate-500 hover:text-red-600 hover:bg-red-50" title="Hapus permanen">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="ui-empty-state">
                                        <div class="flex flex-col items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-slate-200 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                            </svg>
                                            <span class="ui-empty-state-copy">
                                                {{ ($q ?? '') ? 'Tidak ada hasil yang cocok.' : 'Sampah kosong — semua muzakki aman.' }}
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($muzakki->hasPages())
                    <div class="border-t border-slate-200 px-6 py-3">
                        {{ $muzakki->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Modal Konfirmasi Hapus Permanen -->
    <x-modal name="force-delete-muzakki-modal" focusable>
        <form method="POST" x-data="{ id: '', name: '' }" x-on:open-force-delete-modal.window="id = $event.detail.id; name = $event.detail.name; $el.action = '{{ url('/internal/muzakki') }}/' + id + '/force-delete';" class="p-6">
            @csrf
            @method('DELETE')
            <h2 class="text-lg font-bold text-slate-900">
                Hapus <span x-text="name" class="font-bold text-red-600"></span> secara permanen?
            </h2>
            <p class="mt-1 text-sm text-slate-600">
                Data ini akan hilang selamanya dan tidak bisa dipulihkan. Pastikan muzakki ini tidak memiliki riwayat transaksi sebelum melanjutkan.
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
