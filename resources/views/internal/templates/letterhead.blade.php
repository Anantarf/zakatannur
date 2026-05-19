<x-app-layout>
    @php
        $activeTemplate = $templates->firstWhere('is_active', true);
    @endphp

    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="space-y-1 text-center sm:text-left">
                <h2 class="ui-page-title sm:text-2xl">
                    <svg xmlns="http://www.w3.org/2000/svg" class="ui-page-title-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7V6a2 2 0 012-2h6a2 2 0 012 2v1m-9 4h10m-11 8h12a2 2 0 002-2V9a2 2 0 00-2-2H7a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                    Template Kop Surat (PDF)
                </h2>
                <p class="ui-page-title-copy">Kelola template cetak resmi agar hasil dokumen tetap rapi dan konsisten.</p>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="ui-alert ui-alert-success mb-6">
                    <svg xmlns="http://www.w3.org/2000/svg" class="ui-alert-icon text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span class="text-sm font-medium">{{ session('status') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div class="ui-alert ui-alert-error mb-6">
                    <div class="w-full">
                        <div class="ui-alert-title text-red-700">Periksa input:</div>
                    <ul class="mt-2 list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    </div>
                </div>
            @endif

            <div class="ui-card mb-6 overflow-hidden">
                <div class="p-5 sm:p-6">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <div class="text-xs font-black uppercase tracking-[0.18em] text-emerald-700">Template Aktif</div>
                            @if ($activeTemplate)
                                <div class="mt-1 text-lg font-black text-slate-900">v{{ $activeTemplate->version }} - {{ $activeTemplate->original_filename }}</div>
                                <div class="mt-1 text-xs text-slate-500">Dipakai untuk cetak dokumen dan tanda terima.</div>
                            @else
                                <div class="mt-1 text-lg font-black text-amber-800">Belum ada template aktif</div>
                                <div class="mt-1 text-xs text-amber-700">Aktifkan satu template agar cetak dokumen siap digunakan.</div>
                            @endif
                        </div>
                        @if ($activeTemplate)
                            <a href="{{ route('internal.templates.preview', $activeTemplate) }}" target="_blank" class="ui-btn ui-btn-secondary w-full sm:w-auto">Preview Aktif</a>
                        @endif
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <div class="ui-card overflow-hidden">
                    <div class="border-b border-emerald-50 bg-gradient-to-br from-white via-emerald-50/40 to-white px-6 py-5">
                        <div class="text-sm font-black text-slate-900">Upload Template Baru</div>
                        <div class="mt-1 text-xs text-gray-600">Hanya PDF. Ukuran maks 10MB.</div>
                    </div>
                    <div class="p-6 text-gray-900">
                        <form method="POST" action="{{ route('internal.templates.letterhead.store') }}" enctype="multipart/form-data" class="space-y-4">
                            @csrf

                            <div class="rounded-[1.35rem] border border-dashed border-emerald-200 bg-emerald-50/50 p-5">
                                <label class="ui-form-label" for="file">File PDF</label>
                                <input id="file" name="file" type="file" accept="application/pdf" class="block w-full rounded-xl border border-emerald-100 bg-white p-3 text-sm text-slate-700 shadow-sm file:mr-4 file:rounded-lg file:border-0 file:bg-emerald-600 file:px-4 file:py-2 file:text-sm file:font-bold file:text-white hover:file:bg-emerald-700" required />
                                <p class="mt-2 text-xs text-emerald-700">Upload tidak otomatis mengaktifkan template. Aktifkan versi yang benar setelah preview.</p>
                            </div>

                            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center">
                                <a href="{{ route('dashboard') }}" class="ui-btn ui-btn-secondary w-full sm:w-auto">Kembali</a>
                                <button type="submit" class="ui-btn ui-btn-primary w-full sm:w-auto">
                                    Upload
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="ui-card overflow-hidden">
                    <div class="border-b border-slate-100 bg-slate-50/70 px-6 py-5">
                        <div class="text-sm font-black text-slate-900">Daftar Versi</div>
                        <div class="mt-1 text-xs text-gray-600">Aktifkan tepat 1 template untuk dipakai saat cetak.</div>
                    </div>
                    <div class="p-6 text-gray-900">

                        <div class="space-y-3 md:hidden">
                            @if (count($templates) > 0)
                                @foreach ($templates as $t)
                                    <article class="ui-mobile-card">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <div class="text-sm font-bold text-gray-800">v{{ $t->version }}</div>
                                                <div class="mt-1 truncate text-sm text-gray-600" title="{{ $t->original_filename }}">{{ $t->original_filename }}</div>
                                                <div class="mt-1 text-xs text-gray-500">{{ $t->created_at->timezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB</div>
                                            </div>
                                            @if ($t->is_active)
                                                <span class="ui-role-badge ui-role-badge-super bg-emerald-100 text-emerald-800">AKTIF</span>
                                            @else
                                                <span class="ui-role-badge ui-role-badge-staff">NONAKTIF</span>
                                            @endif
                                        </div>

                                        <div class="mt-4 grid grid-cols-1 gap-2">
                                            <a href="{{ route('internal.templates.preview', $t) }}" target="_blank" class="ui-btn ui-btn-secondary w-full px-4 py-3 text-sm text-indigo-700">Preview</a>
                                            @if (!$t->is_active)
                                                <form method="POST" action="{{ route('internal.templates.activate', $t) }}">
                                                    @csrf
                                                    <button type="submit" class="ui-btn ui-btn-primary w-full px-4 py-3 text-sm">Aktifkan</button>
                                                </form>
                                                <form method="POST" action="{{ route('internal.templates.destroy', $t) }}" onsubmit="return confirm('Hapus template ini secara permanen?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="ui-btn ui-btn-danger w-full px-4 py-3 text-sm">Hapus</button>
                                                </form>
                                            @endif
                                        </div>
                                    </article>
                                @endforeach
                            @else
                                <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 p-5 text-center text-sm text-gray-600">Belum ada template.</div>
                            @endif
                        </div>

                        <div class="mt-4 hidden overflow-x-auto w-full md:block">
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
                                        <tr class="border-b align-middle">
                                            <td class="py-2 pr-4 font-semibold">v{{ $t->version }}</td>
                                            <td class="py-2 pr-4">
                                                @if ($t->is_active)
                                                    <span class="ui-role-badge ui-role-badge-super bg-emerald-100 text-emerald-800">AKTIF</span>
                                                @else
                                                    <span class="ui-role-badge ui-role-badge-staff">NONAKTIF</span>
                                                @endif
                                            </td>
                                            <td class="py-2 pr-4">
                                                <div class="max-w-[240px] truncate" title="{{ $t->original_filename }}">{{ $t->original_filename }}</div>
                                                <div class="text-xs text-gray-500">{{ $t->created_at->timezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB</div>
                                            </td>
                                            <td class="py-3">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <a href="{{ route('internal.templates.preview', $t) }}" target="_blank" class="ui-btn ui-btn-secondary px-3 py-2 text-xs text-indigo-700">Preview</a>

                                                    @if (!$t->is_active)
                                                        <form method="POST" action="{{ route('internal.templates.activate', $t) }}">
                                                            @csrf
                                                            <button type="submit" class="ui-btn ui-btn-primary px-3 py-2 text-xs">
                                                                Aktifkan
                                                            </button>
                                                        </form>
                                                        <form method="POST" action="{{ route('internal.templates.destroy', $t) }}" onsubmit="return confirm('Hapus template ini secara permanen?')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="ui-btn ui-btn-danger px-3 py-2 text-xs">
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
                                            <td colspan="4" class="py-6 text-center text-sm text-gray-600">
                                                <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 p-5">Belum ada template.</div>
                                            </td>
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
