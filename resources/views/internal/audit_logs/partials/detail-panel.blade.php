<div x-data="{ open: false }">
    <div x-show="open" x-cloak x-transition class="p-4 bg-blue-50 rounded-lg overflow-x-auto border border-blue-200">
        @if(in_array($log->action, ['Updated.Transaction', 'Created.Transaction']))
            <div class="mb-4 pb-4 border-b border-blue-200">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-xs font-semibold text-brand-600 uppercase tracking-[0.08em]">Ringkasan Transaksi</h4>
                    <span class="text-[10px] text-slate-600 font-sans">{{ $log->metadata['no_transaksi'] ?? '-' }}</span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="bg-blue-100 p-3 rounded-lg border border-blue-200">
                        <div class="text-[10px] text-slate-600 uppercase font-semibold mb-1">Tambah</div>
                        <div class="text-lg font-bold text-brand-600">{{ $log->metadata['summary']['added'] ?? 0 }}</div>
                    </div>
                    <div class="bg-amber-100 p-3 rounded-lg border border-amber-200">
                        <div class="text-[10px] text-slate-600 uppercase font-semibold mb-1">Update</div>
                        <div class="text-lg font-bold text-amber-700">{{ $log->metadata['summary']['updated'] ?? 0 }}</div>
                    </div>
                    <div class="bg-red-100 p-3 rounded-lg border border-red-200">
                        <div class="text-[10px] text-slate-600 uppercase font-semibold mb-1">Hapus</div>
                        <div class="text-lg font-bold text-red-700">{{ $log->metadata['summary']['removed'] ?? 0 }}</div>
                    </div>
                </div>
            </div>

            <div class="space-y-3">
                @if(($log->metadata['totals']['old']['uang'] ?? 0) != ($log->metadata['totals']['new']['uang'] ?? 0))
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-slate-700 font-medium">Total Uang</span>
                        <div class="flex items-center gap-2">
                            <span class="text-slate-600 line-through">{{ \App\Support\Format::rupiah((int)($log->metadata['totals']['old']['uang'] ?? 0)) }}</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-brand-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                            </svg>
                            <span class="text-brand-600 font-bold">{{ \App\Support\Format::rupiah((int)($log->metadata['totals']['new']['uang'] ?? 0)) }}</span>
                        </div>
                    </div>
                @endif
                @if(($log->metadata['totals']['old']['beras'] ?? 0) != ($log->metadata['totals']['new']['beras'] ?? 0))
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-slate-700 font-medium">Total Beras</span>
                        <div class="flex items-center gap-2">
                            <span class="text-slate-600 line-through text-[10px]">{{ \App\Support\Format::kg((float)($log->metadata['totals']['old']['beras'] ?? 0)) }}</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-brand-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                            </svg>
                            <span class="text-brand-600 font-bold">{{ \App\Support\Format::kg((float)($log->metadata['totals']['new']['beras'] ?? 0)) }}</span>
                        </div>
                    </div>
                @endif
            </div>
        @elseif($log->action === 'system.bulk_transaction_cleanup')
            <div class="p-3 bg-amber-100 rounded-lg border border-amber-200">
                <div class="flex items-center gap-2 mb-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-amber-600" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0110 1.944 11.954 11.954 0 0117.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <span class="text-[10px] font-semibold uppercase tracking-[0.08em] text-amber-700">
                        Pembersihan Sistem
                    </span>
                </div>
                <div class="space-y-1">
                    <div class="flex justify-between items-center text-[11px]">
                        <span class="text-slate-600">Periode Data</span>
                        <span class="text-slate-800 font-sans">{{ $log->metadata['start_date'] }} s/d {{ $log->metadata['end_date'] }}</span>
                    </div>
                    <div class="flex justify-between items-center text-[11px]">
                        <span class="text-slate-600">Jumlah Dihapus</span>
                        <span class="text-red-700 font-bold font-sans">{{ $log->metadata['count'] }} Transaksi</span>
                    </div>
                </div>
            </div>
        @elseif(in_array($log->action, ['muzakki.deleted', 'muzakki.restored']))
            @php
                $bgClass = $log->action === 'muzakki.restored' ? 'bg-emerald-100' : 'bg-rose-100';
                $borderClass = $log->action === 'muzakki.restored' ? 'border-emerald-200' : 'border-rose-200';
                $iconClass = $log->action === 'muzakki.restored' ? 'text-emerald-600' : 'text-rose-600';
                $textClass = $log->action === 'muzakki.restored' ? 'text-emerald-700' : 'text-rose-700';
            @endphp
            <div class="p-3 {{ $bgClass }} rounded-lg border {{ $borderClass }}">
                <div class="flex items-center gap-2 mb-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 {{ $iconClass }}" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                    </svg>
                    <span class="text-xs font-semibold uppercase tracking-[0.08em] {{ $textClass }}">
                        Muzakki {{ $log->action === 'muzakki.restored' ? 'Dipulihkan' : 'Dihapus' }}
                    </span>
                </div>
                <div class="flex justify-between items-center text-[11px]">
                    <span class="text-slate-600">Nama Muzakki</span>
                    <span class="text-slate-800 font-bold">{{ $log->metadata['name'] ?? 'Data Lama' }}</span>
                </div>
            </div>
        @elseif(in_array($log->action, ['transaction.delete', 'Deleted.Permanently.Transaction', 'Restored.Transaction']))
            @php
                $attrs = match($log->action) {
                    'Restored.Transaction' => ['bg' => 'bg-blue-100', 'border' => 'border-blue-200', 'icon' => 'text-blue-600', 'text' => 'text-blue-700'],
                    'transaction.delete' => ['bg' => 'bg-pink-100', 'border' => 'border-pink-200', 'icon' => 'text-pink-600', 'text' => 'text-pink-700'],
                    'Deleted.Permanently.Transaction' => ['bg' => 'bg-red-100', 'border' => 'border-red-200', 'icon' => 'text-red-600', 'text' => 'text-red-700'],
                    default => ['bg' => 'bg-red-100', 'border' => 'border-red-200', 'icon' => 'text-red-600', 'text' => 'text-red-700']
                };
            @endphp
            <div class="p-3 {{ $attrs['bg'] }} rounded-lg border {{ $attrs['border'] }}">
                <div class="flex items-center gap-2 mb-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 {{ $attrs['icon'] }}" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="{{ $log->action === 'Restored.Transaction' ? 'M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z' : 'M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z' }}" clip-rule="evenodd" />
                    </svg>
                    <span class="text-xs font-semibold uppercase tracking-[0.08em] {{ $attrs['text'] }}">
                        {{ $log->action === 'Restored.Transaction' ? 'Transaksi Dipulihkan' : ($log->action === 'Deleted.Permanently.Transaction' ? 'Dihapus Permanen' : 'Dipindah ke Sampah') }}
                    </span>
                </div>
                <div class="grid grid-cols-1 gap-1 text-[11px]">
                    <div class="flex justify-between">
                        <span class="text-slate-600">No. Transaksi</span>
                        <span class="text-slate-800 font-sans">{{ $log->metadata['no_transaksi'] ?? '-' }}</span>
                    </div>
                    @if(isset($log->metadata['items_count']))
                        <div class="flex justify-between">
                            <span class="text-slate-600">Jumlah Item</span>
                            <span class="text-slate-800">{{ $log->metadata['items_count'] }} Item</span>
                        </div>
                    @endif
                </div>
            </div>
        @elseif(in_array($log->action, ['login', 'logout']))
            @php
                $attrs = $log->action === 'login'
                    ? ['bg' => 'bg-blue-100', 'border' => 'border-blue-200', 'icon' => 'text-blue-600', 'text' => 'text-blue-700']
                    : ['bg' => 'bg-slate-100', 'border' => 'border-slate-200', 'icon' => 'text-slate-600', 'text' => 'text-slate-700'];
            @endphp
            <div class="flex items-center gap-2 p-3 {{ $attrs['bg'] }} rounded-lg border {{ $attrs['border'] }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 {{ $attrs['icon'] }}" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                </svg>
                <span class="text-xs {{ $attrs['text'] }} font-bold uppercase tracking-wide">
                    Sistem: Berhasil {{ $log->action === 'login' ? 'Masuk (Login)' : 'Keluar (Logout)' }}
                </span>
            </div>
        @elseif(in_array($log->action, ['user.created', 'user.updated']))
            <div class="space-y-2">
                <h4 class="text-[10px] font-semibold text-amber-600 uppercase tracking-[0.08em] border-b border-amber-200 pb-1 flex items-center gap-2">
                    {{ $log->action === 'user.created' ? 'User Baru Dibuat' : 'User Diupdate' }}
                </h4>
                <div class="grid grid-cols-1 gap-1 text-[11px]">
                    <div class="flex justify-between items-center bg-slate-100 p-2 rounded">
                        <span class="text-slate-600">Target User</span>
                        <span class="text-slate-800 font-bold">{{ $log->metadata['name'] ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between items-center bg-slate-100 p-2 rounded">
                        <span class="text-slate-600">Role</span>
                        <span class="text-amber-600 font-bold uppercase">{{ $log->metadata['role'] ?? '-' }}</span>
                    </div>
                </div>
            </div>
        @elseif($log->action === 'transaction.sync_remove_item')
            <div class="space-y-2">
                <h4 class="text-[10px] font-semibold text-red-600 uppercase tracking-[0.08em] mb-2 border-b border-red-200 pb-1">Item Dihapus</h4>
                <div class="grid grid-cols-1 gap-1 text-[11px]">
                    <div class="flex justify-between border-b border-slate-200 pb-1">
                        <span class="text-slate-600">Muzakki</span>
                        <span class="text-slate-800 font-bold text-right">{{ $log->metadata['muzakki'] ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between border-b border-slate-200 pb-1">
                        <span class="text-slate-600">Kategori</span>
                        <span class="text-slate-800 font-bold text-right uppercase">{{ $log->metadata['category'] ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between border-b border-slate-200 pb-1">
                        <span class="text-slate-600">No. Transaksi</span>
                        <span class="text-slate-800 font-sans text-right capitalize">{{ $log->metadata['no_transaksi'] ?? '-' }}</span>
                    </div>
                </div>
            </div>
        @elseif(is_array($log->metadata) && count($log->metadata) > 0)
            <div class="space-y-3">
                @foreach($log->metadata as $key => $val)
                    <div class="bg-slate-100 p-2 rounded border border-slate-200">
                        <span class="block text-[10px] text-slate-600 uppercase font-semibold mb-1 tracking-[0.08em]">{{ Str::headline($key) }}</span>
                        @if(is_array($val))
                            <div class="grid grid-cols-1 gap-1 pl-2 border-l-2 border-slate-200">
                                @foreach($val as $subKey => $subVal)
                                    <div class="flex flex-wrap gap-x-2 text-[10px]">
                                        <span class="text-slate-600">{{ Str::headline($subKey) }}:</span>
                                        <span class="text-brand-600 font-sans not-italic">{{ is_array($subVal) ? json_encode($subVal) : $subVal }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <span class="text-[11px] text-brand-600 font-sans break-all">{{ $val }}</span>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <pre class="text-[10px] text-brand-600 font-mono leading-relaxed bg-slate-100 p-3 rounded border border-slate-200">No Data Available</pre>
        @endif

    </div>
</div>
