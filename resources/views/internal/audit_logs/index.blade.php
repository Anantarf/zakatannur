<x-app-layout>
    <x-slot name="header">
        <div class="space-y-1 text-center sm:text-left">
            <h2 class="ui-page-title">
                <svg xmlns="http://www.w3.org/2000/svg" class="ui-page-title-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z" />
                </svg>
                Log Audit
            </h2>
            <p class="ui-page-title-copy">Riwayat aktivitas penting untuk membantu pelacakan dan akuntabilitas sistem.</p>
        </div>
    </x-slot>

    <div class="py-6 sm:py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <x-zakky-insight
                :tone="$zakkyInsight['tone']"
                :label="$zakkyInsight['label']"
                :message="$zakkyInsight['message']"
                :items="$zakkyInsight['items'] ?? []"
                :generated="$zakkyInsight['generated'] ?? false"
            />

            <div class="ui-card overflow-hidden shadow-md">
                <div class="ui-card-header ui-card-header-slate">
                    <div class="ui-section-accent h-6 w-2"></div>
                    <h3 class="ui-card-header-title">Riwayat Aktivitas</h3>
                </div>
                <div class="p-4 sm:p-6 text-slate-900">

                    <div class="space-y-3 md:hidden">
                        @if (count($logs) > 0)
                            @foreach ($logs as $log)
                                <article class="ui-mobile-card">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <div class="font-sans text-[11px] text-slate-500">{{ $log->created_at->format('d/m/Y H:i:s') }}</div>
                                            <div class="mt-2 font-bold text-slate-800">{{ $log->actorUser->name ?? 'System' }}</div>
                                            <div class="mt-1 text-[11px] font-medium tracking-tight text-slate-400">{{ $log->ip }}</div>
                                        </div>
                                        <div class="shrink-0 text-right">
                                            @include('internal.audit_logs.partials.action-badge', ['log' => $log])
                                        </div>
                                    </div>

                                    <div class="ui-mobile-meta-grid">
                                        <div class="ui-mobile-meta-item">
                                            <p class="ui-mobile-meta-label">Tabel</p>
                                            <span class="font-semibold text-slate-700">{{ class_basename($log->subject_type) }}</span>
                                        </div>
                                        <div class="ui-mobile-meta-item">
                                            <p class="ui-mobile-meta-label">ID</p>
                                            <span class="font-sans text-xs text-slate-500">{{ $log->subject_id ? '#' . substr($log->subject_id, 0, 8) . '...' : '-' }}</span>
                                        </div>
                                    </div>

                                    <div class="mt-4">
                                        @include('internal.audit_logs.partials.detail-panel', ['log' => $log])
                                    </div>
                                </article>
                            @endforeach
                        @else
                            <div class="ui-empty-state">
                                <div class="flex flex-col items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-slate-200 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <span class="ui-empty-state-copy">Belum ada aktivitas yang tercatat.</span>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="hidden overflow-x-auto w-full rounded-2xl border border-slate-100 md:block">
                        <table class="w-full text-left text-sm font-medium text-slate-700">
                            <thead>
                                <tr class="bg-slate-50 text-slate-500 text-xs font-semibold uppercase tracking-[0.08em] border-b border-slate-100">
                                    <th class="px-4 sm:px-6 py-4 text-xs font-semibold uppercase tracking-[0.08em] border-b border-slate-100">
                                        <a href="{{ route('internal.audit_logs.index', ['sort_by' => 'created_at', 'sort_dir' => ($sortBy === 'created_at' && $sortDir === 'desc') ? 'asc' : 'desc', 'page' => 1]) }}" class="flex items-center gap-1 hover:text-slate-700 transition-colors group">
                                            Waktu
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-colors {{ $sortBy === 'created_at' ? 'text-brand-500' : 'text-slate-300' }} {{ $sortBy === 'created_at' && $sortDir === 'asc' ? 'rotate-180' : '' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </a>
                                    </th>
                                    <th class="px-4 sm:px-6 py-4 text-xs font-semibold uppercase tracking-[0.08em] border-b border-slate-100">
                                        <a href="{{ route('internal.audit_logs.index', ['sort_by' => 'petugas', 'sort_dir' => ($sortBy === 'petugas' && $sortDir === 'asc') ? 'desc' : 'asc', 'page' => 1]) }}" class="flex items-center gap-1 hover:text-slate-700 transition-colors group">
                                            Petugas
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-colors {{ $sortBy === 'petugas' ? 'text-brand-500' : 'text-slate-300' }} {{ $sortBy === 'petugas' && $sortDir === 'desc' ? 'rotate-180' : '' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </a>
                                    </th>
                                    <th class="px-4 sm:px-6 py-4 text-xs font-semibold uppercase tracking-[0.08em] border-b border-slate-100">
                                        <a href="{{ route('internal.audit_logs.index', ['sort_by' => 'action', 'sort_dir' => ($sortBy === 'action' && $sortDir === 'asc') ? 'desc' : 'asc', 'page' => 1]) }}" class="flex items-center gap-1 hover:text-slate-700 transition-colors group">
                                            Aksi
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-colors {{ $sortBy === 'action' ? 'text-brand-500' : 'text-slate-300' }} {{ $sortBy === 'action' && $sortDir === 'desc' ? 'rotate-180' : '' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </a>
                                    </th>
                                    <th class="px-4 sm:px-6 py-4">Tabel</th>
                                    <th class="px-4 sm:px-6 py-4">ID</th>
                                    <th class="px-4 sm:px-6 py-4">Detail Perubahan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @if (count($logs) > 0)
                                    @foreach ($logs as $log)
                                    <tr class="transition-colors hover:bg-slate-50">
                                        <td class="px-6 py-4 font-sans text-xs text-slate-500">
                                            {{ $log->created_at->timezone('Asia/Jakarta')->format('d/m/Y H:i:s') }}
                                            <div class="mt-1 font-sans text-[10px] font-bold text-slate-300">WIB</div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex flex-col">
                                                <span class="font-bold text-slate-800">{{ $log->actorUser->name ?? 'System' }}</span>
                                                <span class="text-[10px] text-slate-400 font-medium tracking-tight">{{ $log->ip }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            @include('internal.audit_logs.partials.action-badge', ['log' => $log])
                                        </td>
                                        <td class="px-6 py-4 text-xs font-bold text-slate-500">
                                            {{ class_basename($log->subject_type) }}
                                        </td>
                                        <td class="px-6 py-4 text-xs font-sans text-slate-400">
                                            {{ $log->subject_id ? '#' . substr($log->subject_id, 0, 8) . '...' : '-' }}
                                        </td>
                                        <td class="px-6 py-4">
                                            @include('internal.audit_logs.partials.detail-panel', ['log' => $log])
                                        </td>
                                    </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="6" class="px-6 py-12 text-center">
                                            <div class="flex flex-col items-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-slate-200 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z" />
                                                </svg>
                                                <span class="ui-empty-state-copy">Belum ada aktivitas yang tercatat.</span>
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>

                    <div class="border-t border-slate-100 px-5 py-3">
                        {{ $logs->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
