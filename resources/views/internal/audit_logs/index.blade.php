<x-app-layout>
    <x-slot name="header">
        <div class="space-y-1 text-center sm:text-left">
            <h2 class="ui-page-title">
                <svg xmlns="http://www.w3.org/2000/svg" class="ui-page-title-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z" />
                </svg>
                Riwayat Aktivitas
            </h2>
            <p class="ui-page-title-copy">Catatan semua kegiatan penting yang dilakukan oleh petugas di sistem ini.</p>
        </div>
    </x-slot>

    <div class="py-4 sm:py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            <x-zakky-insight
                :tone="$zakkyInsight['tone']"
                :label="$zakkyInsight['label']"
                :message="$zakkyInsight['message']"
                :items="$zakkyInsight['items'] ?? []"
                :generated="$zakkyInsight['generated'] ?? false"
            />

            <div class="ui-card overflow-hidden">
                {{-- Toolbar --}}
                <div class="ui-toolbar-soft">
                    <form method="GET" action="{{ route('internal.audit_logs.index') }}" class="flex w-full flex-col gap-2 sm:flex-row sm:items-center">
                        <input type="text" name="q" value="{{ $q ?? '' }}" placeholder="Cari aktivitas atau nama petugas..." class="ui-input w-full sm:flex-1" />
                        <div class="flex w-full flex-none items-center gap-2 sm:w-auto">
                            <button type="submit" class="ui-btn ui-btn-secondary flex-1 sm:flex-none px-4 py-2.5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                Cari
                            </button>
                            @if($q ?? null)
                                <a href="{{ route('internal.audit_logs.index') }}" class="ui-btn ui-btn-secondary flex-1 text-center sm:flex-none px-4 py-2.5">
                                    Reset
                                </a>
                            @endif
                        </div>
                    </form>
                </div>

                <div class="p-4 sm:p-6 text-slate-900">

                    {{-- Mobile --}}
                    <div class="space-y-3 md:hidden">
                        @forelse ($logs as $log)
                            <article class="ui-mobile-card">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0 flex-1">
                                        <div class="text-[11px] text-slate-400">
                                            {{ $log->created_at->timezone('Asia/Jakarta')->format('d/m/Y · H:i') }}
                                        </div>
                                        <div class="mt-2 text-sm leading-snug">
                                            @include('internal.audit_logs.partials._narrative', ['log' => $log])
                                        </div>
                                    </div>
                                    <div class="shrink-0 pt-0.5">
                                        @include('internal.audit_logs.partials.action-badge', ['log' => $log])
                                    </div>
                                </div>
                            </article>
                        @empty
                            <div class="ui-empty-state">
                                <div class="flex flex-col items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-slate-200 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <span class="ui-empty-state-copy">Belum ada aktivitas yang tercatat.</span>
                                </div>
                            </div>
                        @endforelse
                    </div>

                    {{-- Desktop --}}
                    <div class="hidden overflow-x-auto w-full rounded-2xl border border-slate-200 md:block">
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr class="ui-table-header">
                                    <th class="px-6 py-4 w-44">
                                        <a href="{{ route('internal.audit_logs.index', ['sort_by' => 'created_at', 'sort_dir' => ($sortBy === 'created_at' && $sortDir === 'desc') ? 'asc' : 'desc', 'page' => 1]) }}" class="flex items-center gap-1 hover:text-slate-700 transition-colors">
                                            Waktu
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform {{ $sortBy === 'created_at' ? 'text-brand-500' : 'text-slate-300' }} {{ $sortBy === 'created_at' && $sortDir === 'asc' ? 'rotate-180' : '' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </a>
                                    </th>
                                    <th class="px-6 py-4">Ringkasan Kegiatan</th>
                                    <th class="px-6 py-4 w-20 text-center">Detail</th>
                                </tr>
                            </thead>
                                @forelse ($logs as $log)
                                    <tbody x-data="{ open: false }">
                                        <tr class="border-b border-slate-100 transition-colors hover:bg-slate-50">
                                            <td class="px-6 py-4">
                                                <div class="flex flex-col gap-0.5">
                                                    <span class="font-bold text-sm text-slate-800">{{ $log->created_at->timezone('Asia/Jakarta')->format('d/m/Y') }}</span>
                                                    <span class="font-medium text-xs text-slate-500">{{ $log->created_at->timezone('Asia/Jakarta')->format('H:i:s') }}</span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="flex flex-col gap-1.5">
                                                    @include('internal.audit_logs.partials._narrative', ['log' => $log])
                                                    @include('internal.audit_logs.partials.action-badge', ['log' => $log])
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 text-center">
                                                <button type="button" @click="open = !open" class="inline-flex items-center justify-center mx-auto text-brand-600 hover:text-brand-700 transition-colors">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                    </svg>
                                                </button>
                                            </td>
                                        </tr>
                                        <tr x-show="open" x-transition x-cloak class="bg-slate-50/50">
                                            <td colspan="3" class="px-6 py-4">
                                                @include('internal.audit_logs.partials.detail-panel', ['log' => $log])
                                            </td>
                                        </tr>
                                    </tbody>
                                @empty
                                    <tbody>
                                        <tr>
                                            <td colspan="3" class="ui-empty-state">
                                                <div class="flex flex-col items-center">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-slate-200 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z" />
                                                    </svg>
                                                    <span class="ui-empty-state-copy">Belum ada aktivitas yang tercatat.</span>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                @endforelse
                        </table>
                    </div>

                    <div class="border-t border-slate-100 px-6 py-3">
                        {{ $logs->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
