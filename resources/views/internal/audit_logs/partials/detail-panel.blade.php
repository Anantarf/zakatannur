<div class="space-y-3">
        @if(in_array($log->action, ['Updated.Transaction', 'Created.Transaction']))
            <div class="bg-white p-3 rounded-lg border border-slate-200">
                <div class="text-sm font-bold text-slate-800 mb-3">Perubahan pada Transaksi #{{ $log->metadata['no_transaksi'] ?? '-' }}</div>

                @if($log->action === 'Created.Transaction')
                    <div class="text-sm text-slate-600">Transaksi baru dibuat dengan data lengkap.</div>
                @else
                    <div class="space-y-2 text-sm">
                        @if(($log->metadata['totals']['old']['uang'] ?? 0) != ($log->metadata['totals']['new']['uang'] ?? 0))
                            <div class="flex justify-between items-center">
                                <span class="text-slate-700">Total Uang</span>
                                <div class="flex items-center gap-2">
                                    <span class="text-slate-500 line-through">{{ \App\Support\Format::rupiah((int)($log->metadata['totals']['old']['uang'] ?? 0)) }}</span>
                                    <span class="text-slate-400">→</span>
                                    <span class="text-slate-900 font-bold">{{ \App\Support\Format::rupiah((int)($log->metadata['totals']['new']['uang'] ?? 0)) }}</span>
                                </div>
                            </div>
                        @endif

                        @if(($log->metadata['totals']['old']['beras'] ?? 0) != ($log->metadata['totals']['new']['beras'] ?? 0))
                            <div class="flex justify-between items-center">
                                <span class="text-slate-700">Total Beras</span>
                                <div class="flex items-center gap-2">
                                    <span class="text-slate-500 line-through">{{ \App\Support\Format::kg((float)($log->metadata['totals']['old']['beras'] ?? 0)) }}</span>
                                    <span class="text-slate-400">→</span>
                                    <span class="text-slate-900 font-bold">{{ \App\Support\Format::kg((float)($log->metadata['totals']['new']['beras'] ?? 0)) }}</span>
                                </div>
                            </div>
                        @endif

                        @if(isset($log->metadata['summary']))
                            <div class="pt-2 border-t border-slate-100 text-xs text-slate-600">
                                <span>{{ $log->metadata['summary']['added'] ?? 0 }} item ditambah,
                                {{ $log->metadata['summary']['updated'] ?? 0 }} diubah,
                                {{ $log->metadata['summary']['removed'] ?? 0 }} dihapus</span>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        @elseif($log->action === 'system.bulk_transaction_cleanup')
            <div class="bg-amber-50 p-3 rounded-lg border border-amber-200">
                <div class="text-sm font-bold text-amber-900 mb-2">Pembersihan Data Sistem</div>
                <div class="space-y-1 text-sm">
                    <div class="flex justify-between">
                        <span class="text-slate-700">Periode</span>
                        <span class="text-slate-900 font-mono text-xs">{{ $log->metadata['start_date'] }} s/d {{ $log->metadata['end_date'] }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-700">Transaksi Dihapus</span>
                        <span class="text-red-700 font-bold">{{ $log->metadata['count'] }} transaksi</span>
                    </div>
                </div>
            </div>
        @elseif(in_array($log->action, ['muzakki.deleted', 'muzakki.restored']))
            @php
                $isRestored = $log->action === 'muzakki.restored';
                $bgClass = $isRestored ? 'bg-emerald-50' : 'bg-red-50';
                $borderClass = $isRestored ? 'border-emerald-200' : 'border-red-200';
                $textClass = $isRestored ? 'text-emerald-900' : 'text-red-900';
            @endphp
            <div class="p-3 {{ $bgClass }} rounded-lg border {{ $borderClass }}">
                <div class="text-sm font-bold {{ $textClass }} mb-2">
                    Muzakki {{ $isRestored ? 'Dipulihkan' : 'Dihapus' }}
                </div>
                <div class="text-sm text-slate-700">
                    <strong>{{ $log->metadata['name'] ?? 'Data Lama' }}</strong>
                </div>
            </div>
        @elseif(in_array($log->action, ['transaction.delete', 'Deleted.Permanently.Transaction', 'Restored.Transaction']))
            @php
                $attrs = match($log->action) {
                    'Restored.Transaction' => ['bg' => 'bg-blue-50', 'border' => 'border-blue-200', 'text' => 'text-blue-900', 'status' => 'Transaksi Dipulihkan'],
                    'transaction.delete' => ['bg' => 'bg-pink-50', 'border' => 'border-pink-200', 'text' => 'text-pink-900', 'status' => 'Dipindah ke Sampah'],
                    'Deleted.Permanently.Transaction' => ['bg' => 'bg-red-50', 'border' => 'border-red-200', 'text' => 'text-red-900', 'status' => 'Dihapus Permanen'],
                    default => ['bg' => 'bg-red-50', 'border' => 'border-red-200', 'text' => 'text-red-900', 'status' => 'Dihapus']
                };
            @endphp
            <div class="p-3 {{ $attrs['bg'] }} rounded-lg border {{ $attrs['border'] }}">
                <div class="text-sm font-bold {{ $attrs['text'] }} mb-2">{{ $attrs['status'] }}</div>
                <div class="space-y-1 text-sm">
                    <div class="flex justify-between">
                        <span class="text-slate-700">Transaksi</span>
                        <span class="text-slate-900 font-mono">{{ $log->metadata['no_transaksi'] ?? '-' }}</span>
                    </div>
                    @if(isset($log->metadata['items_count']))
                        <div class="flex justify-between">
                            <span class="text-slate-700">Item</span>
                            <span class="text-slate-900">{{ $log->metadata['items_count'] }} item</span>
                        </div>
                    @endif
                </div>
            </div>
        @elseif(in_array($log->action, ['login', 'logout']))
            @php
                $isLogin = $log->action === 'login';
                $bgClass = $isLogin ? 'bg-blue-50' : 'bg-slate-50';
                $borderClass = $isLogin ? 'border-blue-200' : 'border-slate-200';
                $textClass = $isLogin ? 'text-blue-900' : 'text-slate-700';
            @endphp
            <div class="p-3 {{ $bgClass }} rounded-lg border {{ $borderClass }}">
                <div class="text-sm font-bold {{ $textClass }}">
                    {{ $isLogin ? 'Berhasil Masuk' : 'Keluar Sistem' }}
                </div>
            </div>
        @elseif(in_array($log->action, ['user.created', 'user.updated']))
            <div class="bg-white p-3 rounded-lg border border-slate-200">
                <div class="text-sm font-bold text-slate-800 mb-2">
                    {{ $log->action === 'user.created' ? 'Petugas Baru Ditambahkan' : 'Data Petugas Diperbarui' }}
                </div>
                <div class="space-y-1 text-sm">
                    <div class="flex justify-between">
                        <span class="text-slate-700">Nama</span>
                        <span class="text-slate-900 font-bold">{{ $log->metadata['name'] ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-700">Jabatan</span>
                        <span class="text-slate-900 font-bold uppercase">{{ $log->metadata['role'] ?? '-' }}</span>
                    </div>
                </div>
            </div>
        @elseif($log->action === 'transaction.sync_remove_item')
            <div class="bg-red-50 p-3 rounded-lg border border-red-200">
                <div class="text-sm font-bold text-red-900 mb-2">Item Dihapus</div>
                <div class="space-y-1 text-sm">
                    <div class="flex justify-between">
                        <span class="text-slate-700">Muzakki</span>
                        <span class="text-slate-900 font-bold">{{ $log->metadata['muzakki'] ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-700">Kategori</span>
                        <span class="text-slate-900 font-bold uppercase">{{ $log->metadata['category'] ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-700">Transaksi</span>
                        <span class="text-slate-900 font-mono text-xs">{{ $log->metadata['no_transaksi'] ?? '-' }}</span>
                    </div>
                </div>
            </div>
        @elseif(is_array($log->metadata) && count($log->metadata) > 0)
            <div class="bg-white p-3 rounded-lg border border-slate-200 text-sm text-slate-600">
                Informasi detail tersimpan di sistem.
            </div>
        @else
            <div class="bg-white p-3 rounded-lg border border-slate-200 text-sm text-slate-600">
                Tidak ada detail perubahan yang tercatat.
            </div>
        @endif

</div>
