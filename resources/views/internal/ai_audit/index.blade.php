<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="ui-page-title">Chatbot Analytics</h2>
                <p class="ui-page-title-copy">Monitor chatbot performance, feedback, and conversation statistics.</p>
            </div>
        </div>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <!-- Stats Cards -->
            <div class="grid gap-4 md:grid-cols-5">
                <div class="ui-card p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-slate-500 uppercase">Total Conversations</p>
                            <p class="mt-1 text-2xl font-bold text-slate-900">{{ $stats['totalChats'] }}</p>
                            <p class="text-xs text-slate-400 mt-1">{{ $stats['total24h'] }} in last 24h</p>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-brand-500 opacity-20" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z" />
                        </svg>
                    </div>
                </div>

                <div class="ui-card p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-slate-500 uppercase">Helpful Rate</p>
                            <p class="mt-1 text-2xl font-bold text-green-600">{{ $stats['helpfulRate'] }}%</p>
                            <p class="text-xs text-slate-400 mt-1">User ratings</p>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-green-500 opacity-20" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M2 10.5a1.5 1.5 0 113 0v-1a1.5 1.5 0 01-3 0v1zM14 14V4h-2v10h2zM4 14V4H2v10h2z" />
                        </svg>
                    </div>
                </div>

                <div class="ui-card p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-slate-500 uppercase">Cache Hit Rate</p>
                            <p class="mt-1 text-2xl font-bold text-blue-600">{{ $stats['cacheHitRate'] }}%</p>
                            <p class="text-xs text-slate-400 mt-1">Performance</p>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-blue-500 opacity-20" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a1 1 0 001 1h12a1 1 0 001-1V6a2 2 0 00-2-2H4zm12 12H4a2 2 0 01-2-2v-4a1 1 0 00-1-1H.5a1.5 1.5 0 011.5 1.5v4A4 4 0 004 20h12a4 4 0 004-4v-4a1.5 1.5 0 01-1.5-1.5H17a1 1 0 00-1-1v4a2 2 0 01-2 2z" clip-rule="evenodd" />
                        </svg>
                    </div>
                </div>

                <div class="ui-card p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-slate-500 uppercase">Error Rate</p>
                            <p class="mt-1 text-2xl font-bold text-amber-600">{{ $stats['errorRate'] }}%</p>
                            <p class="text-xs text-slate-400 mt-1">Failed responses</p>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-amber-500 opacity-20" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </div>
                </div>

                <div class="ui-card p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-slate-500 uppercase">Top Intent</p>
                            <p class="mt-1 text-lg font-bold text-slate-900 truncate">{{ $topIntents->first()?->intent ?? 'N/A' }}</p>
                            <p class="text-xs text-slate-400 mt-1">{{ $topIntents->first()?->count ?? 0 }} conversations</p>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-purple-500 opacity-20" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" />
                            <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 1 1 0 000 2H3a1 1 0 00-1 1v10a1 1 0 001 1h14a1 1 0 001-1V6a1 1 0 00-1-1h-3a1 1 0 000-2 2 2 0 00-2-2H4zm9.707 5.707a1 1 0 00-1.414-1.414L9 12.586l-1.293-1.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Recent Logs -->
            <div class="ui-card overflow-hidden">
                <div class="border-b border-slate-200 px-6 py-4">
                    <h3 class="font-semibold text-slate-900">Recent Chat Logs</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="border-b border-slate-200 bg-slate-50">
                            <tr>
                                <th class="px-6 py-3 text-left font-medium text-slate-700">Session ID</th>
                                <th class="px-6 py-3 text-left font-medium text-slate-700">Question</th>
                                <th class="px-6 py-3 text-left font-medium text-slate-700">Source</th>
                                <th class="px-6 py-3 text-left font-medium text-slate-700">Sentiment</th>
                                <th class="px-6 py-3 text-left font-medium text-slate-700">Time</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            @forelse ($recentLogs as $log)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-6 py-3 font-mono text-xs text-slate-600">{{ substr($log->session_id, 0, 8) }}...</td>
                                    <td class="px-6 py-3 text-slate-800">
                                        <span class="line-clamp-1">{{ $log->question }}</span>
                                    </td>
                                    <td class="px-6 py-3">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                            :class="{
                                                'bg-blue-100 text-blue-800': '{{ $log->source_type }}' === 'ai',
                                                'bg-green-100 text-green-800': '{{ $log->source_type }}' === 'cache',
                                                'bg-purple-100 text-purple-800': '{{ $log->source_type }}' === 'knowledge',
                                                'bg-amber-100 text-amber-800': '{{ $log->source_type }}' === 'error',
                                            }"
                                        >{{ ucfirst($log->source_type) }}</span>
                                    </td>
                                    <td class="px-6 py-3">
                                        @if ($log->sentiment)
                                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                                :class="{
                                                    'bg-red-100 text-red-800': '{{ $log->sentiment }}' === 'frustrated',
                                                    'bg-yellow-100 text-yellow-800': '{{ $log->sentiment }}' === 'confused',
                                                    'bg-gray-100 text-gray-800': '{{ $log->sentiment }}' === 'neutral',
                                                }"
                                            >{{ ucfirst($log->sentiment) }}</span>
                                        @else
                                            <span class="text-slate-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-3 text-slate-500 text-xs">{{ $log->created_at->diffForHumans() }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-slate-500">No chat logs yet</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Source Breakdown -->
            <div class="grid gap-4 md:grid-cols-2">
                <div class="ui-card p-5">
                    <h3 class="font-semibold text-slate-900 mb-4">Response Source Breakdown</h3>
                    <div class="space-y-3">
                        @forelse ($sourceBreakdown as $item)
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-slate-600">{{ ucfirst($item->source_type) }}</span>
                                <div class="flex items-center gap-3">
                                    <div class="h-2 bg-slate-200 rounded-full flex-1" style="width: 120px;">
                                        <div class="h-full bg-brand-500 rounded-full" style="width: {{ ($item->count / $stats['totalChats']) * 100 }}%"></div>
                                    </div>
                                    <span class="text-sm font-semibold text-slate-900 w-12 text-right">{{ $item->count }}</span>
                                </div>
                            </div>
                        @empty
                            <p class="text-slate-400 text-sm">No data available</p>
                        @endforelse
                    </div>
                </div>

                <div class="ui-card p-5">
                    <h3 class="font-semibold text-slate-900 mb-4">Top Intents (Last 7 Days)</h3>
                    <div class="space-y-3">
                        @forelse ($topIntents as $intent)
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-slate-600 truncate">{{ $intent->intent ?? 'Unknown' }}</span>
                                <span class="text-sm font-semibold text-slate-900">{{ $intent->count }}</span>
                            </div>
                        @empty
                            <p class="text-slate-400 text-sm">No intent data available</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
