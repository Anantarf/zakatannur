<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <h2 class="font-bold text-xl sm:text-2xl text-emerald-800 leading-tight flex items-center justify-center sm:justify-start gap-2 text-center sm:text-left">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                Data Muzakki
            </h2>
            <div class="flex items-center justify-center sm:justify-end gap-3 w-full sm:w-auto">
                @if (auth()->check() && in_array(auth()->user()->role, ['admin', 'super_admin'], true))
                    <a href="{{ route('internal.muzakki.trash') }}" class="inline-flex items-center gap-1.5 text-sm font-bold text-gray-500 hover:text-red-600 transition-all bg-white sm:bg-transparent px-4 py-2 sm:p-0 rounded-xl border sm:border-0 border-gray-100 shadow-sm sm:shadow-none">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Sampah
                    </a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
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
                        <div class="w-2 h-6 bg-emerald-500 rounded-full"></div>
                        <h3 class="font-bold text-gray-800">Daftar Muzakki</h3>
                    </div>

                    <form method="GET" action="{{ route('internal.muzakki.index') }}" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 mt-3 sm:mt-0 w-full sm:w-auto">
                        <input type="text" name="q" value="{{ $q }}" placeholder="Cari nama/alamat..." class="rounded-xl border-gray-200 bg-gray-50 px-4 py-2 text-sm font-bold text-gray-600 focus:border-emerald-500 focus:ring-emerald-500 w-full sm:min-w-[200px]" />
                        <div class="flex items-center justify-end gap-2 shrink-0">
                            <button type="submit" class="p-2 bg-gray-800 text-white rounded-xl hover:bg-gray-900 transition-all flex-1 sm:flex-none flex justify-center items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </button>
                            @if($q)
                                <a href="{{ route('internal.muzakki.index') }}" class="p-2 text-gray-400 hover:text-red-500 transition-all" title="Reset">
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
                                <th class="px-3 sm:px-6 py-4">Nama</th>
                                <th class="px-3 sm:px-6 py-4">No HP</th>
                                <th class="px-3 sm:px-6 py-4">Alamat</th>
                                <th class="px-3 sm:px-6 py-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @if (count($muzakki) > 0)
                                @foreach ($muzakki as $m)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-3 sm:px-6 py-4 font-bold text-gray-800">{!! \App\Support\Format::highlight($m->name, $q) !!}</td>
                                    <td class="px-3 sm:px-6 py-4 text-gray-500">{{ $m->phone ?? '-' }}</td>
                                    <td class="px-3 sm:px-6 py-4 text-gray-500 text-sm">{!! \App\Support\Format::highlight($m->address ?? '-', $q) !!}</td>
                                    <td class="px-3 sm:px-6 py-4 text-center whitespace-nowrap">
                                        <div class="flex items-center justify-center gap-3">
                                            <a class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-blue-50 text-sm font-bold text-blue-600 hover:bg-blue-100 transition-all" href="{{ route('internal.muzakki.edit', ['muzakki' => $m->id]) }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                                Ubah
                                            </a>
                                            
                                            <button type="button" x-data x-on:click="$dispatch('open-modal', 'delete-muzakki-modal'); $dispatch('open-delete-modal', { id: {{ $m->id }}, name: '{{ addslashes($m->name) }}' })" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-red-50 text-sm font-bold text-red-600 hover:bg-red-100 transition-all">
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
                                    <td colspan="4" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-gray-200 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                            </svg>
                                            <span class="text-sm font-bold text-gray-400">
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
                    <div class="px-6 py-4 border-t border-gray-50">
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
            <h2 class="text-lg font-medium text-gray-900">
                Pindahkan Muzakki <span x-text="name" class="font-bold text-emerald-700"></span> ke Tempat Sampah?
            </h2>
            <p class="mt-1 text-sm text-gray-600">
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
