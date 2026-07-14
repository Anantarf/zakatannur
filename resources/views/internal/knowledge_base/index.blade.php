<x-app-layout>
    <x-slot name="header">
        <h2 class="ui-page-title">
            <svg xmlns="http://www.w3.org/2000/svg" class="ui-page-title-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
            </svg>
            Gudang Solusi AI
        </h2>
        <p class="ui-page-title-copy">Kelola basis pengetahuan (Knowledge Base) yang digunakan AI sebagai landasan dalam memberikan konsultasi Zakat.</p>
    </x-slot>

    <div class="py-4 sm:py-6">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="ui-alert ui-alert-success">
                    <svg xmlns="http://www.w3.org/2000/svg" class="ui-alert-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span class="font-medium">{{ session('status') }}</span>
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {{-- Form Tambah/Edit --}}
                <div class="lg:col-span-1">
                    <div class="ui-card overflow-hidden">
                        <div class="border-b border-slate-100 bg-slate-50 px-4 py-3 sm:px-5">
                            <h3 class="text-sm font-semibold text-slate-800">
                                {{ isset($editing) ? 'Edit Referensi' : 'Tambah Referensi Baru' }}
                            </h3>
                        </div>
                        <div class="p-4 sm:p-5">
                            <form action="{{ isset($editing) ? route('internal.knowledge-base.update', $editing) : route('internal.knowledge-base.store') }}" method="POST" class="space-y-4">
                                @csrf
                                @if(isset($editing))
                                    @method('PUT')
                                @endif

                                <div>
                                    <label for="slug" class="ui-form-label">Kode / Slug</label>
                                    <input type="text" name="slug" id="slug" value="{{ old('slug', $editing->slug ?? '') }}" class="ui-input w-full" placeholder="zakat-mal-syarat" required {{ isset($editing) ? 'readonly' : '' }}>
                                    <x-input-error :messages="$errors->get('slug')" class="mt-2" />
                                </div>

                                <div>
                                    <label for="title" class="ui-form-label">Judul Referensi</label>
                                    <input type="text" name="title" id="title" value="{{ old('title', $editing->title ?? '') }}" class="ui-input w-full" required>
                                    <x-input-error :messages="$errors->get('title')" class="mt-2" />
                                </div>

                                <div>
                                    <label for="source_label" class="ui-form-label">Sumber Landasan</label>
                                    <input type="text" name="source_label" id="source_label" value="{{ old('source_label', $editing->source_label ?? '') }}" class="ui-input w-full" placeholder="Contoh: Fatwa MUI / BAZNAS">
                                    <x-input-error :messages="$errors->get('source_label')" class="mt-2" />
                                </div>

                                <div>
                                    <label for="keywords" class="ui-form-label">Kata Kunci Pencarian (Pisahkan dgn koma)</label>
                                    <input type="text" name="keywords" id="keywords" value="{{ old('keywords', isset($editing) ? implode(', ', $editing->keywords ?? []) : '') }}" class="ui-input w-full" placeholder="zakat mal, syarat, nishab">
                                    <x-input-error :messages="$errors->get('keywords')" class="mt-2" />
                                </div>

                                <div>
                                    <label for="answer" class="ui-form-label">Isi / Jawaban Referensi</label>
                                    <textarea name="answer" id="answer" rows="5" class="ui-input w-full resize-y" required>{{ old('answer', $editing->answer ?? '') }}</textarea>
                                    <p class="mt-1 text-xs text-slate-500">Gunakan bahasa yang jelas. Teks ini akan dibaca oleh AI untuk menjawab pertanyaan user.</p>
                                    <x-input-error :messages="$errors->get('answer')" class="mt-2" />
                                </div>

                                <div>
                                    <label class="ui-settings-check border-emerald-100 text-emerald-900">
                                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $editing->is_active ?? true) ? 'checked' : '' }} class="mt-0.5 rounded border-slate-300 text-emerald-600 shadow-sm focus:ring-emerald-500" />
                                        <span class="text-sm font-medium">Aktif (Dapat dicari AI)</span>
                                    </label>
                                </div>

                                <div class="pt-2 flex justify-end gap-2">
                                    @if(isset($editing))
                                        <a href="{{ route('internal.knowledge-base.index') }}" class="ui-btn ui-btn-secondary">Batal</a>
                                    @endif
                                    <button type="submit" class="ui-btn ui-btn-primary">
                                        {{ isset($editing) ? 'Simpan Perubahan' : 'Tambahkan' }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- Tabel Data --}}
                <div class="lg:col-span-2">
                    <div class="ui-card overflow-hidden">
                        <div class="border-b border-slate-100 bg-slate-50 px-4 py-3 sm:px-5">
                            <h3 class="text-sm font-semibold text-slate-800">Daftar Referensi Tersimpan</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm text-slate-600">
                                <thead class="bg-slate-50/50 text-xs text-slate-500">
                                    <tr>
                                        <th scope="col" class="px-4 py-3 font-medium sm:px-5">Judul & Sumber</th>
                                        <th scope="col" class="px-4 py-3 font-medium sm:px-5">Preview Jawaban</th>
                                        <th scope="col" class="px-4 py-3 font-medium sm:px-5 w-24">Status</th>
                                        <th scope="col" class="px-4 py-3 font-medium sm:px-5 text-right w-24">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @forelse($entries as $entry)
                                        <tr class="hover:bg-slate-50 transition-colors">
                                            <td class="px-4 py-3 sm:px-5">
                                                <div class="font-medium text-slate-900">{{ $entry->title }}</div>
                                                <div class="text-xs text-slate-500 mt-0.5">
                                                    @if($entry->source_label)
                                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-indigo-50 text-indigo-700">
                                                            {{ $entry->source_label }}
                                                        </span>
                                                    @endif
                                                    <span class="ml-1 text-slate-400">#{{ $entry->slug }}</span>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 sm:px-5">
                                                <div class="line-clamp-2 text-xs" title="{{ $entry->answer }}">
                                                    {{ $entry->answer }}
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 sm:px-5">
                                                @if($entry->is_active)
                                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2 py-1 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
                                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Aktif
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-50 px-2 py-1 text-xs font-medium text-slate-600 ring-1 ring-inset ring-slate-500/20">
                                                        <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span> Nonaktif
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 sm:px-5 text-right space-x-2">
                                                <a href="{{ route('internal.knowledge-base.edit', $entry) }}" class="text-indigo-600 hover:text-indigo-900 text-xs font-medium">Edit</a>
                                                <form action="{{ route('internal.knowledge-base.destroy', $entry) }}" method="POST" class="inline-block" onsubmit="return confirm('Hapus referensi ini?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-900 text-xs font-medium">Hapus</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="px-4 py-8 text-center text-slate-500">
                                                Belum ada data referensi. Silakan tambahkan melalui form di samping.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
