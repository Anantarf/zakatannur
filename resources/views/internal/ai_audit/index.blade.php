<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-1 text-center sm:text-left">
                <h2 class="ui-page-title">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 shrink-0 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                    </svg>
                    AI Audit Assistant
                </h2>
                <p class="ui-page-title-copy">Generate ringkasan aktivitas audit dan rekomendasi tindak lanjut berbasis AI.</p>
            </div>
        </div>
    </x-slot>

    <div class="py-5 sm:py-8">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">

            @if (session('status'))
                <div class="ui-alert ui-alert-success">
                    <svg xmlns="http://www.w3.org/2000/svg" class="ui-alert-icon text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span class="font-medium">{{ session('status') }}</span>
                </div>
            @endif

            {{-- Form generate ringkasan --}}
            <div class="ui-card p-5" x-data="{ loading: false }">
                <h3 class="mb-4 text-base font-semibold text-slate-800">Buat Ringkasan Audit Baru</h3>
                <form method="POST" action="{{ route('internal.ai_audit.generate') }}" @submit="loading = true" class="flex flex-col gap-4 sm:flex-row sm:items-end">
                    @csrf
                    <div class="flex-1">
                        <x-input-label for="date_from" value="Dari Tanggal" />
                        <x-text-input id="date_from" name="date_from" type="date" class="mt-1 block w-full"
                            value="{{ old('date_from', now()->format('Y-m-d')) }}" :disabled="loading" required />
                        <x-input-error :messages="$errors->get('date_from')" class="mt-1" />
                    </div>
                    <div class="flex-1">
                        <x-input-label for="date_to" value="Sampai Tanggal" />
                        <x-text-input id="date_to" name="date_to" type="date" class="mt-1 block w-full"
                            value="{{ old('date_to', now()->format('Y-m-d')) }}" :disabled="loading" required />
                        <x-input-error :messages="$errors->get('date_to')" class="mt-1" />
                    </div>
                    <div>
                        <button type="submit" :disabled="loading" class="ui-btn ui-btn-primary w-full sm:w-auto disabled:opacity-60 disabled:cursor-not-allowed">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" :class="{ 'hidden': loading }">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            <span x-show="!loading">Generate Ringkasan</span>
                            <span x-show="loading" class="inline-flex items-center gap-2">
                                <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Memproses...
                            </span>
                        </button>
                    </div>
                </form>
                <p class="mt-3 text-xs text-slate-400">AI akan menganalisis log aktivitas dan menandai aktivitas sensitif pada rentang tanggal yang dipilih.</p>
            </div>

            {{-- Riwayat ringkasan --}}
            <div class="ui-card overflow-hidden">
                <div class="border-b border-slate-100 px-5 py-3">
                    <h3 class="text-sm font-semibold text-slate-700">Riwayat Ringkasan AI</h3>
                </div>

                @if ($summaries->isEmpty())
                    <div class="px-5 py-10 text-center text-sm text-slate-400">
                        Belum ada ringkasan. Generate ringkasan pertama di atas.
                    </div>
                @else
                    <div class="divide-y divide-slate-100">
                        @foreach ($summaries as $s)
                            <a href="{{ route('internal.ai_audit.show', $s->id) }}"
                               class="flex flex-col gap-1 px-5 py-4 transition hover:bg-slate-50 sm:flex-row sm:items-center sm:justify-between">
                                <div class="space-y-0.5">
                                    <div class="text-sm font-medium text-slate-800">
                                        {{ $s->date_from->format('d/m/Y') }}
                                        @if ($s->date_from->ne($s->date_to))
                                            – {{ $s->date_to->format('d/m/Y') }}
                                        @endif
                                    </div>
                                    <div class="text-xs text-slate-500">
                                        {{ $s->total_activities }} aktivitas &bull;
                                        @if ($s->sensitive_activities_count > 0)
                                            <span class="text-amber-600 font-medium">{{ $s->sensitive_activities_count }} perlu ditinjau</span>
                                        @else
                                            <span class="text-emerald-600">Tidak ada aktivitas sensitif</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex items-center gap-3 text-xs text-slate-400">
                                    <span>{{ $s->generatedBy?->name ?? '-' }}</span>
                                    <span>{{ $s->created_at->diffForHumans() }}</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
