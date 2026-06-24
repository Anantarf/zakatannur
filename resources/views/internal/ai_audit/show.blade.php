<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-1 text-center sm:text-left">
                <h2 class="ui-page-title">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 shrink-0 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                    </svg>
                    Hasil AI Audit
                </h2>
                <p class="ui-body-muted">
                    Periode: {{ $summary->date_from->format('d/m/Y') }}
                    @if ($summary->date_from->ne($summary->date_to))
                        – {{ $summary->date_to->format('d/m/Y') }}
                    @endif
                </p>
            </div>
            <a href="{{ route('internal.ai_audit.index') }}" class="ui-btn ui-btn-secondary w-full sm:w-auto">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Kembali
            </a>
        </div>
    </x-slot>

    <div class="py-5 sm:py-8">
        <div class="mx-auto max-w-4xl space-y-5 px-4 sm:px-6 lg:px-8">

            {{-- Stat cards --}}
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                <div class="ui-card px-4 py-4 text-center">
                    <div class="text-2xl font-bold text-slate-800">{{ $summary->total_activities }}</div>
                    <div class="mt-0.5 text-xs text-slate-500">Total Aktivitas</div>
                </div>
                <div class="ui-card px-4 py-4 text-center {{ $summary->sensitive_activities_count > 0 ? 'ring-2 ring-amber-200' : '' }}">
                    <div class="text-2xl font-bold {{ $summary->sensitive_activities_count > 0 ? 'text-amber-600' : 'text-emerald-600' }}">
                        {{ $summary->sensitive_activities_count }}
                    </div>
                    <div class="mt-0.5 text-xs text-slate-500">Perlu Ditinjau</div>
                </div>
                <div class="ui-card px-4 py-4 text-center col-span-2 sm:col-span-1">
                    <div class="text-sm font-semibold text-slate-700">{{ $summary->generatedBy?->name ?? '-' }}</div>
                    <div class="mt-0.5 text-xs text-slate-500">{{ $summary->created_at->format('d/m/Y H:i') }}</div>
                </div>
            </div>

            {{-- Ringkasan --}}
            <div class="ui-card p-5">
                <div class="mb-3 flex items-center gap-2">
                    <span class="inline-flex items-center rounded-full bg-violet-100 px-2.5 py-0.5 text-xs font-semibold text-violet-700">Ringkasan AI</span>
                </div>
                <div class="whitespace-pre-wrap text-sm leading-relaxed text-slate-700">{{ $summary->summary }}</div>
            </div>

            {{-- Rekomendasi --}}
            <div class="ui-card p-5">
                <div class="mb-3 flex items-center gap-2">
                    <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-700">Rekomendasi Tindak Lanjut</span>
                </div>
                <div class="whitespace-pre-wrap text-sm leading-relaxed text-slate-700">{{ $summary->recommendation }}</div>
            </div>

            {{-- Detail aktivitas sensitif (dari context_snapshot) --}}
            @php
                $sensitiveActions = $summary->context_snapshot['sensitive_actions'] ?? [];
                $sensitiveFlags = $summary->context_snapshot['sensitive_flags'] ?? [];
            @endphp

            @if (count($sensitiveActions) > 0 || count($sensitiveFlags) > 0)
                <div class="ui-card overflow-hidden">
                    <div class="border-b border-slate-100 px-5 py-3">
                        <h3 class="text-sm font-semibold text-slate-700">Detail Aktivitas Sensitif</h3>
                    </div>

                    @if (count($sensitiveActions) > 0)
                        <div class="px-5 py-4">
                            <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">Log Aktivitas</div>
                            <div class="space-y-2">
                                @foreach ($sensitiveActions as $a)
                                    <div class="flex items-start gap-3 rounded-lg bg-slate-50 px-3 py-2">
                                        <span class="mt-0.5 inline-block rounded bg-amber-100 px-1.5 py-0.5 text-xs font-medium text-amber-700">{{ $a['action_label'] }}</span>
                                        <div class="min-w-0 flex-1">
                                            <span class="text-sm text-slate-700">{{ $a['actor_name'] }}</span>
                                            <span class="text-xs text-slate-400"> ({{ $a['actor_role'] }}) &bull; {{ $a['time'] }}</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if (count($sensitiveFlags) > 0)
                        <div class="border-t border-slate-100 px-5 py-4">
                            <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">Transaksi Berindikasi Risiko</div>
                            <div class="space-y-2">
                                @foreach ($sensitiveFlags as $f)
                                    <div class="flex flex-wrap items-center gap-2 rounded-lg bg-slate-50 px-3 py-2">
                                        <a href="{{ route('internal.anomalies.show', $f['no_transaksi']) }}" class="text-sm font-mono font-medium text-brand-700 hover:underline">
                                            {{ $f['no_transaksi'] }}
                                        </a>
                                        <span class="rounded bg-red-100 px-1.5 py-0.5 text-xs font-medium text-red-700">{{ $f['risk_level'] }}</span>
                                        @foreach ($f['flags'] as $flag)
                                            <span class="rounded bg-slate-200 px-1.5 py-0.5 text-xs text-slate-600">{{ $flag }}</span>
                                        @endforeach
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Ringkasan per pengguna --}}
            @php $actorSummary = $summary->context_snapshot['actor_summary'] ?? []; @endphp
            @if (count($actorSummary) > 0)
                <div class="ui-card overflow-hidden">
                    <div class="border-b border-slate-100 px-5 py-3">
                        <h3 class="text-sm font-semibold text-slate-700">Aktivitas per Pengguna</h3>
                    </div>
                    <div class="divide-y divide-slate-100">
                        @foreach ($actorSummary as $actor)
                            <div class="flex items-center justify-between px-5 py-3">
                                <div>
                                    <span class="text-sm font-medium text-slate-700">{{ $actor['name'] }}</span>
                                    <span class="ml-2 text-xs text-slate-400">{{ $actor['role'] }}</span>
                                </div>
                                <span class="text-sm font-semibold text-slate-800">{{ $actor['count'] }} aktivitas</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
