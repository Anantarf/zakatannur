<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-slate-200">
                <div class="p-5 sm:p-8 text-slate-900">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 sm:mb-8 text-center sm:text-left">
                        <div>
                            <h2 class="text-xl sm:text-2xl font-black text-slate-800 tracking-tight">Audit Logs</h2>
                            <p class="text-xs sm:text-sm text-slate-500 mt-1">Riwayat aktivitas transaksi dan perubahan data sistem.</p>
                        </div>
                        <div class="flex items-center justify-center sm:justify-end gap-3">
                            @if(false && auth()->user()->isSuperAdmin())
                                <!-- cleanup button disabled for safety -->
                                <button type="button" x-data x-on:click="$dispatch('open-modal', 'cleanup-transactions-modal')" class="inline-flex items-center gap-2 rounded-lg bg-red-50 text-red-700 text-[10px] sm:text-xs font-bold px-3 py-2 border border-red-100 shadow-sm hover:bg-red-100 transition-all active:scale-95 group">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-red-500 transition-transform group-hover:rotate-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                    PEMBERSIHAN DATA
                                </button>
                            @endif
                            <span class="px-4 py-2 bg-emerald-50 text-emerald-700 text-[10px] sm:text-xs font-bold rounded-lg uppercase tracking-widest w-fit border border-emerald-100 shadow-sm">
                                Super Admin Only
                            </span>
                        </div>
                    </div>

                    <div class="overflow-x-auto w-full">
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
                                            {{ $log->created_at->format('d/m/Y H:i:s') }}
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex flex-col">
                                                <span class="font-bold text-slate-800">{{ $log->actorUser->name ?? 'System' }}</span>
                                                <span class="text-[10px] text-slate-400 font-medium tracking-tight">{{ $log->ip }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            @php
                                                $actionClass = match(true) {
                                                    $log->action === 'Created.Transaction' || str_contains($log->action, 'created') => 'bg-emerald-100 text-emerald-700',
                                                    $log->action === 'Updated.Transaction' || str_contains($log->action, 'updated') => 'bg-amber-100 text-amber-700',
                                                    $log->action === 'transaction.delete' => 'bg-pink-100 text-pink-700',
                                                    $log->action === 'Deleted.Permanently.Transaction' => 'bg-red-900 text-white',
                                                    $log->action === 'Restored.Transaction' || str_contains($log->action, 'restored') => 'bg-indigo-100 text-indigo-700',
                                                    $log->action === 'login' => 'bg-blue-100 text-blue-700',
                                                    $log->action === 'logout' => 'bg-slate-200 text-slate-700',
                                                    str_contains($log->action, 'deleted') => 'bg-red-100 text-red-700',
                                                    default => 'bg-slate-100 text-slate-700'
                                                };
                                            @endphp
                                            <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider {{ $actionClass }}">
                                                {{ $log->action }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-xs font-bold text-slate-500">
                                            {{ class_basename($log->subject_type) }}
                                        </td>
                                        <td class="px-6 py-4 text-xs font-mono text-slate-400">
                                            #{{ substr($log->subject_id, 0, 8) }}...
                                        </td>
                                        <td class="px-6 py-4">
                                            <div x-data="{ open: false }">
                                                <button @click="open = !open" class="text-xs font-bold text-emerald-600 hover:text-emerald-700 flex items-center gap-1 transition-colors">
                                                    <span>View Data</span>
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 transform transition-transform" :class="open ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                    </svg>
                                                </button>
                                                <div x-show="open" x-cloak x-transition class="mt-4 p-5 bg-slate-900 rounded-xl overflow-x-auto shadow-2xl border border-slate-700">
                                                    @if(in_array($log->action, ['Updated.Transaction', 'Created.Transaction']))
                                                        <!-- (Sudah ada di turn sebelumnya) -->
                                                        <div class="mb-4 pb-4 border-b border-slate-700/50">
                                                            <div class="flex items-center justify-between mb-3">
                                                                <h4 class="text-xs font-black text-emerald-400 uppercase tracking-widest">Ringkasan Transaksi</h4>
                                                                <span class="text-[10px] text-slate-500 font-mono">{{ $log->metadata['no_transaksi'] ?? '-' }}</span>
                                                            </div>
                                                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                                                <div class="bg-slate-800/50 p-3 rounded-lg border border-slate-700/50">
                                                                    <div class="text-[9px] text-slate-500 uppercase font-bold mb-1">Tambah</div>
                                                                    <div class="text-lg font-black text-emerald-500">{{ $log->metadata['summary']['added'] ?? 0 }}</div>
                                                                </div>
                                                                <div class="bg-slate-800/50 p-3 rounded-lg border border-slate-700/50">
                                                                    <div class="text-[9px] text-slate-500 uppercase font-bold mb-1">Update</div>
                                                                    <div class="text-lg font-black text-amber-500">{{ $log->metadata['summary']['updated'] ?? 0 }}</div>
                                                                </div>
                                                                <div class="bg-slate-800/50 p-3 rounded-lg border border-slate-700/50">
                                                                    <div class="text-[9px] text-slate-500 uppercase font-bold mb-1">Hapus</div>
                                                                    <div class="text-lg font-black text-red-500">{{ $log->metadata['summary']['removed'] ?? 0 }}</div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="space-y-3">
                                                            @if(($log->metadata['totals']['old']['uang'] ?? 0) != ($log->metadata['totals']['new']['uang'] ?? 0))
                                                                <div class="flex items-center justify-between text-xs">
                                                                    <span class="text-slate-400 font-medium">Total Uang</span>
                                                                    <div class="flex items-center gap-2">
                                                                        <span class="text-slate-500 line-through">{{ \App\Support\Format::rupiah((int)($log->metadata['totals']['old']['uang'] ?? 0)) }}</span>
                                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                                                        </svg>
                                                                        <span class="text-emerald-400 font-black">{{ \App\Support\Format::rupiah((int)($log->metadata['totals']['new']['uang'] ?? 0)) }}</span>
                                                                    </div>
                                                                </div>
                                                            @endif
                                                            @if(($log->metadata['totals']['old']['beras'] ?? 0) != ($log->metadata['totals']['new']['beras'] ?? 0))
                                                                <div class="flex items-center justify-between text-xs">
                                                                    <span class="text-slate-400 font-medium">Total Beras</span>
                                                                    <div class="flex items-center gap-2">
                                                                        <span class="text-slate-500 line-through text-[10px]">{{ \App\Support\Format::kg((float)($log->metadata['totals']['old']['beras'] ?? 0)) }}</span>
                                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                                                        </svg>
                                                                        <span class="text-emerald-400 font-black">{{ \App\Support\Format::kg((float)($log->metadata['totals']['new']['beras'] ?? 0)) }}</span>
                                                                    </div>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    @elseif($log->action === 'system.bulk_transaction_cleanup')
                                                        <div class="p-3 bg-slate-900 rounded-lg border border-slate-700 shadow-inner">
                                                            <div class="flex items-center gap-2 mb-2">
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-amber-400" viewBox="0 0 20 20" fill="currentColor">
                                                                    <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                                                </svg>
                                                                <span class="text-[10px] font-black uppercase tracking-[0.2em] text-amber-400">
                                                                    Pembersihan Sistem
                                                                </span>
                                                            </div>
                                                            <div class="space-y-1">
                                                                <div class="flex justify-between items-center text-[11px]">
                                                                    <span class="text-slate-500 italic">Periode Data</span>
                                                                    <span class="text-slate-200 font-mono">{{ $log->metadata['start_date'] }} s/d {{ $log->metadata['end_date'] }}</span>
                                                                </div>
                                                                <div class="flex justify-between items-center text-[11px]">
                                                                    <span class="text-slate-500 italic">Jumlah Dihapus</span>
                                                                    <span class="text-red-400 font-bold font-mono">{{ $log->metadata['count'] }} Transaksi</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @elseif(in_array($log->action, ['muzakki.deleted', 'muzakki.restored']))
                                                        <div class="p-3 bg-{{ $log->action === 'muzakki.restored' ? 'emerald' : 'rose' }}-900/20 rounded-lg border border-{{ $log->action === 'muzakki.restored' ? 'emerald' : 'rose' }}-800/30">
                                                            <div class="flex items-center gap-2 mb-2">
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-{{ $log->action === 'muzakki.restored' ? 'emerald' : 'rose' }}-400" viewBox="0 0 20 20" fill="currentColor">
                                                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                                                                </svg>
                                                                <span class="text-xs font-black uppercase tracking-wider text-{{ $log->action === 'muzakki.restored' ? 'emerald' : 'rose' }}-400">
                                                                    Muzakki {{ $log->action === 'muzakki.restored' ? 'Dipulihkan' : 'Dihapus' }}
                                                                </span>
                                                            </div>
                                                            <div class="flex justify-between items-center text-[11px]">
                                                                <span class="text-slate-500 italic">Nama Muzakki</span>
                                                                <span class="text-slate-200 font-bold">{{ $log->metadata['name'] ?? 'Data Lama' }}</span>
                                                            </div>
                                                        </div>
                                                    @elseif(in_array($log->action, ['transaction.delete', 'Deleted.Permanently.Transaction', 'Restored.Transaction']))
                                                        @php
                                                            $boxClasses = match($log->action) {
                                                                'Restored.Transaction' => 'bg-blue-900/20 border-blue-800/30 text-blue-400',
                                                                'transaction.delete' => 'bg-pink-900/20 border-pink-800/30 text-pink-400',
                                                                'Deleted.Permanently.Transaction' => 'bg-red-900/20 border-red-800/30 text-red-400',
                                                                default => 'bg-red-900/20 border-red-800/30 text-red-400'
                                                            };
                                                            $boxClassesArray = explode(' ', $boxClasses);
                                                            $bgClass = $boxClassesArray[0];
                                                            $borderClass = $boxClassesArray[1];
                                                            $textClass = $boxClassesArray[2];
                                                        @endphp
                                                        <div class="p-3 {{ $bgClass }} rounded-lg border {{ $borderClass }}">
                                                            <div class="flex items-center gap-2 mb-2">
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 {{ $textClass }}" viewBox="0 0 20 20" fill="currentColor">
                                                                    <path fill-rule="evenodd" d="{{ $log->action === 'Restored.Transaction' ? 'M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z' : 'M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z' }}" clip-rule="evenodd" />
                                                                </svg>
                                                                <span class="text-xs font-black uppercase tracking-wider {{ $textClass }}">
                                                                    {{ $log->action === 'Restored.Transaction' ? 'Transaksi Dipulihkan' : ($log->action === 'Deleted.Permanently.Transaction' ? 'Dihapus Permanen' : 'Dipindah ke Sampah') }}
                                                                </span>
                                                            </div>
                                                            <div class="grid grid-cols-1 gap-1 text-[11px]">
                                                                <div class="flex justify-between">
                                                                    <span class="text-slate-500">No. Transaksi</span>
                                                                    <span class="text-slate-300 font-mono">{{ $log->metadata['no_transaksi'] ?? '-' }}</span>
                                                                </div>
                                                                @if(isset($log->metadata['items_count']))
                                                                    <div class="flex justify-between">
                                                                        <span class="text-slate-500">Jumlah Item</span>
                                                                        <span class="text-slate-300">{{ $log->metadata['items_count'] }} Item</span>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    @elseif(in_array($log->action, ['login', 'logout']))
                                                        @php
                                                            $authClasses = $log->action === 'login' 
                                                                ? 'bg-blue-900/20 border-blue-800/30 text-blue-400 text-blue-300'
                                                                : 'bg-slate-900/20 border-slate-800/30 text-slate-400 text-slate-300';
                                                            $authClassesArray = explode(' ', $authClasses);
                                                            $authBgClass = $authClassesArray[0];
                                                            $authBorderClass = $authClassesArray[1];
                                                            $authIconClass = $authClassesArray[2];
                                                            $authTextClass = $authClassesArray[3];
                                                        @endphp
                                                        <div class="flex items-center gap-2 p-3 {{ $authBgClass }} rounded-lg border {{ $authBorderClass }}">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 {{ $authIconClass }}" viewBox="0 0 20 20" fill="currentColor">
                                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                                            </svg>
                                                            <span class="text-xs {{ $authTextClass }} font-bold uppercase tracking-wide">
                                                                Sistem: Berhasil {{ $log->action === 'login' ? 'Masuk (Login)' : 'Keluar (Logout)' }}
                                                            </span>
                                                        </div>
                                                    @elseif(in_array($log->action, ['user.created', 'user.updated']))
                                                        <div class="space-y-2">
                                                            <h4 class="text-[10px] font-black text-amber-400 uppercase tracking-widest border-b border-amber-900/30 pb-1 flex items-center gap-2">
                                                                {{ $log->action === 'user.created' ? 'User Baru Dibuat' : 'User Diupdate' }}
                                                            </h4>
                                                            <div class="grid grid-cols-1 gap-1 text-[11px]">
                                                                <div class="flex justify-between items-center bg-slate-800/20 p-2 rounded">
                                                                    <span class="text-slate-500">Target User</span>
                                                                    <span class="text-slate-200 font-black">{{ $log->metadata['name'] ?? '-' }}</span>
                                                                </div>
                                                                <div class="flex justify-between items-center bg-slate-800/20 p-2 rounded">
                                                                    <span class="text-slate-500">Role</span>
                                                                    <span class="text-amber-500 font-bold uppercase">{{ $log->metadata['role'] ?? '-' }}</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @elseif($log->action === 'transaction.sync_remove_item')
                                                        <div class="space-y-2">
                                                            <h4 class="text-[10px] font-black text-red-400 uppercase tracking-widest mb-2 border-b border-red-900/30 pb-1">Item Dihapus</h4>
                                                            <div class="grid grid-cols-1 gap-1 text-[11px]">
                                                                <div class="flex justify-between border-b border-slate-800/50 pb-1">
                                                                    <span class="text-slate-500">Muzakki</span>
                                                                    <span class="text-slate-300 font-bold text-right">{{ $log->metadata['muzakki'] ?? '-' }}</span>
                                                                </div>
                                                                <div class="flex justify-between border-b border-slate-800/50 pb-1">
                                                                    <span class="text-slate-500">Kategori</span>
                                                                    <span class="text-slate-300 font-bold text-right uppercase">{{ $log->metadata['category'] ?? '-' }}</span>
                                                                </div>
                                                                <div class="flex justify-between border-b border-slate-800/50 pb-1">
                                                                    <span class="text-slate-500">No. Transaksi</span>
                                                                    <span class="text-slate-300 font-mono text-right capitalize">{{ $log->metadata['no_transaksi'] ?? '-' }}</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @elseif(is_array($log->metadata) && count($log->metadata) > 0)
                                                        <div class="space-y-3">
                                                            @foreach($log->metadata as $key => $val)
                                                                <div class="bg-slate-800/20 p-2 rounded border border-slate-700/30">
                                                                    <span class="block text-[9px] text-slate-500 uppercase font-bold mb-1 tracking-wider">{{ Str::headline($key) }}</span>
                                                                    @if(is_array($val))
                                                                        <div class="grid grid-cols-1 gap-1 pl-2 border-l-2 border-slate-700">
                                                                            @foreach($val as $subKey => $subVal)
                                                                                <div class="flex flex-wrap gap-x-2 text-[10px]">
                                                                                    <span class="text-slate-500">{{ Str::headline($subKey) }}:</span>
                                                                                    <span class="text-emerald-400 font-mono italic">{{ is_array($subVal) ? json_encode($subVal) : $subVal }}</span>
                                                                                </div>
                                                                            @endforeach
                                                                        </div>
                                                                    @else
                                                                        <span class="text-[11px] text-emerald-400 font-mono break-all">{{ $val }}</span>
                                                                    @endif
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @else
                                                        <pre class="text-[10px] text-emerald-400 font-mono leading-relaxed bg-slate-800/50 p-3 rounded border border-slate-700/30">No Data Available</pre>
                                                    @endif

                                                    @if(is_array($log->metadata) && count($log->metadata) > 0)
                                                        <div class="pt-3 mt-4 border-t border-slate-700/50">
                                                            <button @click="$el.nextElementSibling.classList.toggle('hidden')" class="text-[9px] text-slate-600 hover:text-slate-400 font-bold uppercase tracking-widest flex items-center gap-1 transition-all">
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-2 w-2" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd" /></svg>
                                                                Toggle Debug Info (JSON)
                                                            </button>
                                                            <pre class="hidden mt-4 text-[10px] text-emerald-400/40 font-mono leading-relaxed bg-slate-950 p-4 rounded-xl border border-slate-800 shadow-inner">{{ json_encode($log->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="6" class="px-6 py-12 text-center text-slate-500 italic">
                                            Belum ada aktivitas yang tercatat.
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-8">
                        {{ $logs->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
    @if(auth()->user()->isSuperAdmin())
        <x-modal name="cleanup-transactions-modal" focusable maxWidth="md">
            <form method="POST" action="{{ route('internal.audit_logs.cleanup_transactions') }}" class="p-6">
                @csrf
                <div class="flex items-center gap-3 mb-4">
                    <div class="p-3 bg-red-100 rounded-xl">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </div>
                    <h2 class="text-xl font-black text-slate-800 uppercase tracking-tight">Pembersihan Database</h2>
                </div>

                <div class="p-4 bg-red-50 border border-red-100 rounded-xl mb-6">
                    <p class="text-sm text-red-800 font-bold flex items-center gap-2 mb-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                        PERINGATAN KERAS!
                    </p>
                    <p class="text-xs text-red-700 leading-relaxed">
                        Aksi ini akan menghapus **SELURUH** data transaksi (termasuk yang sudah di sampah) dalam rentang tanggal berikut secara **PERMANEN**. Data yang sudah dihapus tidak dapat dikembalikan dengan cara apapun.
                    </p>
                </div>

                <div class="space-y-4 mb-8">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Mulai Dari</label>
                            <input type="date" name="start_date" required class="w-full rounded-xl border-slate-200 bg-slate-50 text-sm font-bold text-slate-700 focus:ring-emerald-500 focus:border-emerald-500">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Sampai Dengan</label>
                            <input type="date" name="end_date" required class="w-full rounded-xl border-slate-200 bg-slate-50 text-sm font-bold text-slate-700 focus:ring-emerald-500 focus:border-emerald-500">
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 p-1">
                    <button type="button" x-on:click="$dispatch('close')" class="px-5 py-2.5 text-xs font-bold text-slate-500 hover:text-slate-700 transition-colors">
                        BATAL
                    </button>
                    <button type="submit" onclick="return confirm('APAKAH ANDA BENAR-BENAR YAKIN?\n\nSemua transaksi dalam rentang tanggal tersebut akan dihancurkan selamanya dari sistem.')" class="bg-red-600 hover:bg-red-700 text-white px-6 py-2.5 rounded-xl text-xs font-black shadow-lg shadow-red-200 transition-all active:scale-95 uppercase tracking-widest">
                        YA, HAPUS PERMANEN
                    </button>
                </div>
            </form>
        </x-modal>
    @endif
</x-app-layout>
