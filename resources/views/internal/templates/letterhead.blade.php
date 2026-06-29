<x-app-layout>
    @php
        $activeTemplate = $templates->firstWhere('is_active', true);
    @endphp

    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="space-y-1 text-center sm:text-left">
                <h2 class="ui-page-title">
                    <svg xmlns="http://www.w3.org/2000/svg" class="ui-page-title-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7V6a2 2 0 012-2h6a2 2 0 012 2v1m-9 4h10m-11 8h12a2 2 0 002-2V9a2 2 0 00-2-2H7a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                    Template Kop Surat (PDF)
                </h2>
                <p class="ui-page-title-copy">Kelola template cetak resmi agar hasil dokumen kuitansi tetap rapi dan seragam.</p>
            </div>
        </div>
    </x-slot>

    <div class="py-5 sm:py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4 sm:space-y-6">
            @if (session('status'))
                <div class="ui-alert ui-alert-success">
                    <svg xmlns="http://www.w3.org/2000/svg" class="ui-alert-icon text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span class="text-sm font-medium">{{ session('status') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div class="ui-alert ui-alert-error">
                    <div class="w-full">
                        <div class="ui-alert-title text-red-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="ui-alert-icon" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                            Periksa masukan Anda:
                        </div>
                        <ul class="list-disc pl-10 space-y-1 text-sm">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <div class="ui-card overflow-hidden">
                <div class="p-5 flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-start gap-4 w-full">
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-50 text-brand-600 shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div class="flex flex-col justify-center h-full flex-grow min-w-0">
                            <div class="text-xs font-bold text-brand-700 uppercase tracking-wider mb-1">Template Aktif Saat Ini</div>
                            @if ($activeTemplate)
                                <div class="text-xl font-bold text-slate-900 truncate" title="{{ $activeTemplate->original_filename }}">{{ $activeTemplate->original_filename }}</div>
                                <div class="mt-1 text-sm text-slate-500">Otomatis dipakai saat mencetak kuitansi.</div>
                                <div class="mt-4 sm:hidden">
                                    <a href="{{ route('internal.templates.preview', $activeTemplate) }}" target="_blank" class="ui-btn ui-btn-primary w-full px-5 py-2.5">Preview Cetak</a>
                                </div>
                            @else
                                <div class="text-xl font-bold text-slate-900">Belum ada template</div>
                                <div class="mt-1 text-sm text-slate-500">Silakan unggah template baru di bawah.</div>
                            @endif
                        </div>
                    </div>
                    @if ($activeTemplate)
                        <div class="hidden sm:block shrink-0 pl-6 border-l border-slate-100">
                            <a href="{{ route('internal.templates.preview', $activeTemplate) }}" target="_blank" class="ui-btn ui-btn-primary px-6 py-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                Preview Cetak
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            <div class="ui-card overflow-hidden">
                <div class="border-b border-slate-200 bg-slate-50/50 p-5">
                    <h3 class="font-bold text-slate-800 text-base">Ganti Template Baru</h3>
                    <p class="text-xs text-slate-500 mt-1">Mengunggah file baru akan otomatis menggantikan template sebelumnya secara permanen.</p>
                </div>
                <div class="p-5 sm:p-6" x-data="{ hasFile: false, isSubmitting: false, fileName: '' }">
                    <form method="POST" action="{{ route('internal.templates.letterhead.store') }}" enctype="multipart/form-data" @submit="if(!hasFile) { $event.preventDefault(); return false; } isSubmitting = true">
                        @csrf

                        <div class="relative flex flex-col items-center justify-center w-full px-4 py-10 border-2 border-dashed rounded-xl transition-colors duration-200"
                             :class="hasFile ? 'border-brand-400 bg-brand-50/50' : 'border-slate-300 bg-slate-50 hover:bg-slate-100 hover:border-slate-400'">
                            <input id="file" name="file" type="file" accept="application/pdf" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" required 
                                   @change="hasFile = $event.target.files.length > 0; fileName = hasFile ? $event.target.files[0].name : ''" />
                            
                            <div class="flex flex-col items-center text-center pointer-events-none">
                                <svg x-show="!hasFile" xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 text-slate-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                </svg>
                                <svg x-show="hasFile" xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 text-brand-500 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" x-cloak>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                
                                <template x-if="!hasFile">
                                    <div>
                                        <p class="text-sm font-bold text-slate-700">Klik atau seret file PDF ke area ini</p>
                                        <p class="mt-1.5 text-xs font-medium text-slate-500">Maksimal ukuran file 10MB</p>
                                    </div>
                                </template>
                                <template x-if="hasFile">
                                    <div>
                                        <p class="text-sm font-bold text-brand-700" x-text="fileName"></p>
                                        <p class="mt-1.5 text-xs font-medium text-slate-500">File siap diunggah</p>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end mt-6">
                            <button type="submit" class="ui-btn ui-btn-primary w-full sm:w-auto px-8 py-3" :disabled="!hasFile || isSubmitting" :class="{'opacity-50 cursor-not-allowed': !hasFile}">
                                <template x-if="isSubmitting">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="animate-spin h-5 w-5 mr-2 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                                    </svg>
                                </template>
                                <span x-text="isSubmitting ? 'Mengunggah...' : 'Upload & Terapkan'" class="font-bold"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
