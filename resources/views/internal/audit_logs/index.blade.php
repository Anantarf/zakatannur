<x-app-layout>
    <x-slot name="header">
        <div class="space-y-1 text-center sm:text-left">
            <h2 class="ui-page-title">
                <svg xmlns="http://www.w3.org/2000/svg" class="ui-page-title-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z" />
                </svg>
                Audit Logs
            </h2>
            <p class="ui-page-title-copy">Riwayat aktivitas penting untuk membantu pelacakan dan akuntabilitas sistem.</p>
        </div>
    </x-slot>

    <div class="py-6 sm:py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <div class="rounded-2xl border border-emerald-100 bg-emerald-50/70 p-4">
                    <div class="text-xs font-black uppercase tracking-[0.18em] text-emerald-700">Total Log</div>
                    <div class="mt-1 text-2xl font-black text-emerald-950">{{ $totalLogs }}</div>
                </div>
                <div class="rounded-2xl border border-slate-100 bg-white p-4 shadow-sm">
                    <div class="text-xs font-black uppercase tracking-[0.18em] text-slate-500">Log Terbaru</div>
                    <div class="mt-1 text-sm font-black text-slate-800">
                        {{ $latestLog?->created_at?->timezone('Asia/Jakarta')->format('d/m/Y H:i') ?? '-' }}
                    </div>
                </div>
                <div class="rounded-2xl border border-blue-100 bg-blue-50/70 p-4">
                    <div class="text-xs font-black uppercase tracking-[0.18em] text-blue-700">Akses</div>
                    <div class="mt-1 text-sm font-black text-blue-950">Super Admin Only</div>
                </div>
            </div>

            <div class="ui-card overflow-hidden shadow-md">
                <div class="ui-card-header ui-card-header-neutral justify-between">
                    <div class="flex items-center gap-2">
                        <div class="ui-section-accent h-6 w-2"></div>
                        <div>
                            <h3 class="ui-card-header-title">Riwayat Aktivitas</h3>
                            <p class="text-xs text-slate-500">Klik judul kolom untuk mengurutkan data.</p>
                        </div>
                    </div>
                    <span class="hidden rounded-full border border-emerald-100 bg-emerald-50 px-3 py-1 text-[10px] font-black uppercase tracking-widest text-emerald-700 sm:inline-flex">
                        Super Admin
                    </span>
                </div>
                <div class="p-4 sm:p-6 text-slate-900">

                    <div class="space-y-3 md:hidden">
                        @if (count($logs) > 0)
                            @foreach ($logs as $log)
                                <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <div class="font-mono text-[11px] text-slate-500">{{ $log->created_at->format('d/m/Y H:i:s') }}</div>
                                            <div class="mt-2 font-bold text-slate-800">{{ $log->actorUser->name ?? 'System' }}</div>
                                            <div class="mt-1 text-[11px] font-medium tracking-tight text-slate-400">{{ $log->ip }}</div>
                                        </div>
                                        <div class="shrink-0 text-right">
                                            @include('internal.audit_logs.partials.action-badge', ['log' => $log])
                                        </div>
                                    </div>

                                    <div class="mt-3 space-y-2 rounded-xl bg-slate-50 px-3 py-3 text-sm">
                                        <div class="flex items-start justify-between gap-3">
                                            <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">Tabel</span>
                                            <span class="font-semibold text-slate-700">{{ class_basename($log->subject_type) }}</span>
                                        </div>
                                        <div class="flex items-start justify-between gap-3">
                                            <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">ID</span>
                                            <span class="font-mono text-xs text-slate-500">#{{ substr($log->subject_id, 0, 8) }}...</span>
                                        </div>
                                    </div>

                                    <div class="mt-4">
                                        @include('internal.audit_logs.partials.detail-panel', ['log' => $log])
                                    </div>
                                </article>
                            @endforeach
                        @else
                            <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-6 py-12 text-center text-slate-500">
                                Belum ada aktivitas yang tercatat.
                            </div>
                        @endif
                    </div>

                    <div class="hidden overflow-x-auto w-full rounded-2xl border border-slate-100 md:block">
                        <table class="w-full text-left text-sm font-medium text-slate-700">
                            <thead>
                                <tr class="bg-slate-50 text-slate-500 text-xs font-bold uppercase tracking-widest border-b border-slate-100">
                                    <th class="px-4 sm:px-6 py-4 text-xs font-bold uppercase tracking-widest border-b border-slate-100">
                                        <a href="{{ route('internal.audit_logs.index', ['sort_by' => 'created_at', 'sort_dir' => ($sortBy === 'created_at' && $sortDir === 'desc') ? 'asc' : 'desc']) }}" class="flex items-center gap-1 hover:text-slate-700 transition-colors group">
                                            Waktu
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-colors {{ $sortBy === 'created_at' ? 'text-emerald-500' : 'text-slate-300' }} {{ $sortBy === 'created_at' && $sortDir === 'asc' ? 'rotate-180' : '' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </a>
                                    </th>
                                    <th class="px-4 sm:px-6 py-4 text-xs font-bold uppercase tracking-widest border-b border-slate-100">
                                        <a href="{{ route('internal.audit_logs.index', ['sort_by' => 'petugas', 'sort_dir' => ($sortBy === 'petugas' && $sortDir === 'asc') ? 'desc' : 'asc']) }}" class="flex items-center gap-1 hover:text-slate-700 transition-colors group">
                                            Petugas
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-colors {{ $sortBy === 'petugas' ? 'text-emerald-500' : 'text-slate-300' }} {{ $sortBy === 'petugas' && $sortDir === 'desc' ? 'rotate-180' : '' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </a>
                                    </th>
                                    <th class="px-4 sm:px-6 py-4 text-xs font-bold uppercase tracking-widest border-b border-slate-100">
                                        <a href="{{ route('internal.audit_logs.index', ['sort_by' => 'action', 'sort_dir' => ($sortBy === 'action' && $sortDir === 'asc') ? 'desc' : 'asc']) }}" class="flex items-center gap-1 hover:text-slate-700 transition-colors group">
                                            Aksi
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-colors {{ $sortBy === 'action' ? 'text-emerald-500' : 'text-slate-300' }} {{ $sortBy === 'action' && $sortDir === 'desc' ? 'rotate-180' : '' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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
                                    <tr class="hover:bg-slate-50/50 transition-colors">
                                        <td class="px-6 py-4 font-mono text-xs text-slate-500">
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
                                        <td class="px-6 py-4 text-xs font-mono text-slate-400">
                                            #{{ substr($log->subject_id, 0, 8) }}...
                                        </td>
                                        <td class="px-6 py-4">
                                            @include('internal.audit_logs.partials.detail-panel', ['log' => $log])
                                        </td>
                                    </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                                            <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 p-5">Belum ada aktivitas yang tercatat.</div>
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6">
                        {{ $logs->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
