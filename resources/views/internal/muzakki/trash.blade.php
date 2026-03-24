<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <h2 class="font-bold text-xl sm:text-2xl text-emerald-800 leading-tight flex items-center justify-center sm:justify-start gap-2 text-center sm:text-left">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-amber-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                Tempat Sampah Muzakki
            </h2>
            <a href="{{ route('internal.muzakki.index') }}" class="inline-flex justify-center items-center gap-2 rounded-xl bg-white border border-gray-100 px-4 py-3 sm:py-2 text-sm font-bold text-gray-500 hover:text-emerald-700 hover:bg-emerald-50 transition-all w-full sm:w-auto shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Kembali
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="flex items-center gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-800 shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-amber-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                </svg>
                <div>
                    <p class="text-sm font-bold">Data Muzakki di sampah akan dihapus permanen secara otomatis setelah <span class="underline">30 hari</span>.</p>
                    <p class="text-xs mt-0.5 font-medium text-amber-700">Pastikan Muzakki tidak memiliki riwayat transaksi sebelum dihapus permanen.</p>
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
                        Mohon Perbaiki Kesalahan Berikut:
                    </div>
                    <ul class="list-disc pl-10 space-y-1 text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif


            {{-- Table --}}
            <div class="bg-white rounded-2xl shadow-md border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-50 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-center gap-2">
                        <div class="w-2 h-6 bg-amber-500 rounded-full"></div>
                        <h3 class="font-bold text-gray-800">Daftar Tempat Sampah</h3>
                    </div>

                    <form method="GET" action="{{ route('internal.muzakki.trash') }}" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 mt-3 sm:mt-0 w-full sm:w-auto">
                        <input type="text" name="q" value="{{ $q }}" placeholder="Cari nama/alamat..." class="rounded-xl border-gray-200 bg-gray-50 px-4 py-2 text-xs font-bold text-gray-600 focus:border-emerald-500 focus:ring-emerald-500 w-full sm:min-w-[200px]" />
                        <div class="flex items-center justify-end gap-2 shrink-0">
                            <button type="submit" class="p-2 bg-gray-800 text-white rounded-xl hover:bg-gray-900 transition-all flex-1 sm:flex-none flex justify-center items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </button>
                            @if($q)
                                <a href="{{ route('internal.muzakki.trash') }}" class="p-2 text-gray-400 hover:text-red-500 transition-all" title="Reset">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </a>
                            @endif
                        </div>
                    </form>
                </div>
                <div class="overflow-x-auto w-full">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 text-left text-xs font-bold text-gray-400 uppercase tracking-widest border-b border-gray-100">
                                <th class="px-6 py-4">Nama</th>
                                <th class="px-6 py-4">Alamat</th>
                                <th class="px-6 py-4 text-center">Waktu Hapus</th>
                                <th class="px-6 py-4 text-center">Sisa Waktu</th>
                                <th class="px-6 py-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @if (count($muzakki) > 0)
                                @foreach ($muzakki as $m)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 font-bold text-gray-800">
                                        {!! \App\Support\Format::highlight($m->name, $q) !!}
                                        <div class="text-[10px] text-gray-400 font-medium">HP: {{ $m->phone ?? '-' }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-gray-500 text-xs">{!! \App\Support\Format::highlight($m->address ?? '-', $q) !!}</td>
                                    <td class="px-6 py-4 text-center text-gray-500 text-xs whitespace-nowrap">
                                        {{ $m->deleted_at_formatted }}
                                    </td>
                                    <td class="px-6 py-4 text-center text-xs whitespace-nowrap">
                                        @if($m->days_left !== null)
                                            <span class="inline-flex items-center px-2 py-1 rounded-md font-bold {{ $m->days_left <= 7 ? 'bg-red-50 text-red-600' : 'bg-gray-50 text-gray-600' }}">
                                                {{ $m->days_left > 0 ? $m->days_left . ' Hari Lagi' : 'Hapus Hari Ini' }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-center whitespace-nowrap">
                                        <div class="flex items-center justify-center gap-3">
                                            <form method="POST" action="{{ route('internal.muzakki.restore', $m->id) }}">
                                                @csrf
                                                <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-bold text-white shadow-sm hover:bg-emerald-700 transition-all">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                                    </svg>
                                                    Pulihkan
                                                </button>
                                            </form>

                                            <button type="button" x-data x-on:click="$dispatch('open-modal', 'force-delete-muzakki-modal'); $dispatch('open-force-delete-modal', { id: {{ $m->id }}, name: '{{ addslashes($m->name) }}' })" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-red-600 text-xs font-bold text-white shadow-sm hover:bg-red-700 transition-all">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                                Hapus Permanen
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-gray-200 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                            </svg>
                                            <span class="text-sm font-bold text-gray-400">
                                                {{ ($q ?? '') ? 'Data tidak ditemukan.' : 'Belum ada data muzakki di sampah.' }}
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
                @if ($muzakki->hasPages())
                    <div class="px-6 py-4 border-t border-gray-50">
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
            <h2 class="text-lg font-medium text-gray-900">
                Hapus Permanen Muzakki <span x-text="name" class="font-bold text-red-600"></span>?
            </h2>
            <p class="mt-1 text-sm text-gray-600">
                Data ini akan dihapus selamanya dari sistem dan tidak dapat dipulihkan kembali. Pastikan muzakki tidak memiliki riwayat transaksi aktif.
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
