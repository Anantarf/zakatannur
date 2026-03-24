<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <h2 class="font-bold text-2xl text-emerald-800 leading-tight">
                Template Kop Surat (PDF)
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-900">
                    <div class="font-semibold">Periksa input:</div>
                    <ul class="mt-2 list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <div class="text-sm font-semibold">Upload Template Baru</div>
                        <div class="mt-1 text-xs text-gray-600">Hanya PDF. Ukuran maks 10MB.</div>

                        <form method="POST" action="{{ route('internal.templates.letterhead.store') }}" enctype="multipart/form-data" class="mt-4 space-y-4">
                            @csrf

                            <div>
                                <label class="block text-sm font-semibold" for="file">File PDF</label>
                                <input id="file" name="file" type="file" accept="application/pdf" class="mt-1 block w-full text-sm" required />
                            </div>

                            <div class="flex items-center gap-3">
                                <button type="submit" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                    Upload
                                </button>
                                <a href="{{ route('dashboard') }}" class="text-sm font-semibold text-gray-700 hover:underline">Kembali ke Dashboard</a>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <div class="text-sm font-semibold">Daftar Versi</div>
                        <div class="mt-1 text-xs text-gray-600">Aktifkan tepat 1 template untuk dipakai saat cetak.</div>

                        <div class="mt-4 overflow-x-auto w-full">
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="border-b text-left text-gray-600">
                                        <th class="py-2 pr-4">Versi</th>
                                        <th class="py-2 pr-4">Status</th>
                                        <th class="py-2 pr-4">Nama File</th>
                                        <th class="py-2">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if (count($templates) > 0)
                                        @foreach ($templates as $t)
                                        <tr class="border-b align-top">
                                            <td class="py-2 pr-4 font-semibold">v{{ $t->version }}</td>
                                            <td class="py-2 pr-4">
                                                @if ($t->is_active)
                                                    <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-800">AKTIF</span>
                                                @else
                                                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-700">NONAKTIF</span>
                                                @endif
                                            </td>
                                            <td class="py-2 pr-4">
                                                <div class="max-w-[240px] truncate" title="{{ $t->original_filename }}">{{ $t->original_filename }}</div>
                                                <div class="text-xs text-gray-500">{{ $t->created_at->timezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB</div>
                                            </td>
                                            <td class="py-2">
                                                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                                                    <a href="{{ route('internal.templates.preview', $t) }}" target="_blank" class="text-sm font-semibold text-indigo-700 hover:underline">Preview</a>

                                                    @if (!$t->is_active)
                                                        <form method="POST" action="{{ route('internal.templates.activate', $t) }}">
                                                            @csrf
                                                            <button type="submit" class="text-sm font-semibold text-emerald-700 hover:underline">
                                                                Aktifkan
                                                            </button>
                                                        </form>
                                                        <form method="POST" action="{{ route('internal.templates.destroy', $t) }}" onsubmit="return confirm('Hapus template ini secara permanen?')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="text-sm font-semibold text-red-600 hover:underline">
                                                                Hapus
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                        @endforeach
                                    @else
                                        <tr>
                                            <td colspan="4" class="py-4 text-sm text-gray-600">Belum ada template.</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
