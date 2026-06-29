@php
    $actor = $log->actorUser->name ?? 'Sistem';
    $meta  = is_array($log->metadata) ? $log->metadata : [];
    $noTrx = $meta['no_transaksi'] ?? null;

    $text = match(true) {
        $log->action === 'login'                                  => "{$actor} masuk ke sistem",
        $log->action === 'logout'                                 => "{$actor} keluar dari sistem",
        $log->action === 'Created.Transaction'                    => "{$actor} membuat transaksi baru",
        $log->action === 'Updated.Transaction'                    => "{$actor} mengubah data transaksi",
        $log->action === 'transaction.delete'                     => "{$actor} memindahkan transaksi ke sampah",
        $log->action === 'Deleted.Permanently.Transaction'        => "{$actor} menghapus permanen sebuah transaksi",
        $log->action === 'Restored.Transaction'                   => "{$actor} memulihkan transaksi dari sampah",
        $log->action === 'settings.period.updated'                => "{$actor} mengubah pengaturan periode zakat",
        $log->action === 'transaction.risk_review_status_updated' => "{$actor} memperbarui status tinjauan transaksi",
        $log->action === 'transaction.sync_remove_item'           => "{$actor} menghapus item dari transaksi",
        $log->action === 'system.bulk_transaction_cleanup'        => "Sistem membersihkan " . ($meta['count'] ?? '?') . " transaksi lama secara otomatis",
        $log->action === 'template.deleted'                       => "{$actor} menghapus template pembayaran",
        $log->action === 'user.created'                           => "{$actor} menambahkan petugas baru" . (!empty($meta['name']) ? ": {$meta['name']}" : ''),
        $log->action === 'user.updated'                           => "{$actor} memperbarui data petugas" . (!empty($meta['name']) ? ": {$meta['name']}" : ''),
        $log->action === 'muzakki.deleted'                        => "{$actor} menghapus data muzakki" . (!empty($meta['name']) ? ": {$meta['name']}" : ''),
        $log->action === 'muzakki.restored'                       => "{$actor} memulihkan data muzakki" . (!empty($meta['name']) ? ": {$meta['name']}" : ''),
        default                                                   => "{$actor} melakukan perubahan di sistem",
    };
@endphp
<span class="font-semibold text-slate-800">{{ $text }}</span>
@if($noTrx)
    <span class="ml-1 font-mono text-xs text-slate-400">{{ $noTrx }}</span>
@endif
