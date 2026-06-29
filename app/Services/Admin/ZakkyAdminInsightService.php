<?php

namespace App\Services\Admin;

use App\Models\AuditLog;
use App\Models\TransactionRiskReview;
use Carbon\Carbon;

class ZakkyAdminInsightService
{
    private const SENSITIVE_ACTIONS = [
        'Updated.Transaction',
        'transaction.updated_after_receipt_printed',
        'transaction.delete',
        'Deleted.Permanently.Transaction',
        'Restored.Transaction',
        'settings.period.updated',
        'transaction.risk_review_status_updated',
    ];

    /**
     * @return array{label: string, tone: string, message: string, items: array<int, string>, generated: bool}
     */
    public function auditLogInsight(): array
    {
        $today = Carbon::now(config('zakat.timezone'))->startOfDay();
        $cacheKey = 'zakky:audit-log:' . $today->format('Y-m-d');

        return cache()->remember($cacheKey, 3600, function () use ($today) {
            $logs = AuditLog::query()
                ->with('actorUser')
                ->where('created_at', '>=', $today)
                ->get(['id', 'action', 'created_at', 'metadata', 'actor_user_id']);

            if ($logs->isEmpty()) {
                return $this->insight(
                    'Informasi dari Zakky',
                    'success',
                    'Belum ada aktivitas penting yang tercatat hari ini.',
                );
            }

            // Deteksi aktivitas mencurigakan
            $riskLogs = $logs->filter(fn($log) => $log->has_risk_flags)->values();

            if ($riskLogs->isNotEmpty()) {
                $items = [];
                $riskLogs->take(3)->each(function ($log) use (&$items) {
                    $riskDescriptions = [
                        'perubahan_nominal_besar' => 'perubahan nominal > 50%',
                        'penghapusan_multipel' => 'penghapusan > 3 item',
                        'pembayar_berubah' => 'pembayar diubah',
                    ];

                    $flags = collect($log->risk_flags)
                        ->map(fn($f) => $riskDescriptions[$f] ?? $f)
                        ->implode(', ');

                    $userName = $log->actorUser ? $log->actorUser->name : 'Sistem';
                    $items[] = "{$userName} ({$flags}) jam {$log->created_at->format('H:i')}";
                });

                return $this->insight(
                    'Perhatian dari Zakky',
                    'warning',
                    "Terdeteksi {$riskLogs->count()} aktivitas mencurigakan hari ini yang perlu dicek. Sebaiknya verifikasi perubahan nominal besar, penghapusan item multipel, dan perubahan pembayar.",
                    $items,
                );
            }

            $sensitiveLogs = $logs->whereIn('action', self::SENSITIVE_ACTIONS);
            $highImpactCount = $logs->whereIn('action', [
                'Deleted.Permanently.Transaction',
                'settings.period.updated',
                'transaction.updated_after_receipt_printed',
            ])->count();

            if ($highImpactCount > 0) {
                return $this->insight(
                    'Peringatan dari Zakky',
                    'warning',
                    "Ada {$highImpactCount} aktivitas berdampak tinggi hari ini. Periksa perubahan periode, penghapusan permanen, atau perubahan transaksi setelah kwitansi dicetak sebelum laporan digunakan.",
                );
            }

            if ($sensitiveLogs->isNotEmpty()) {
                return $this->insight(
                    'Perhatian dari Zakky',
                    'attention',
                    "Ada {$sensitiveLogs->count()} aktivitas sensitif hari ini. Fokuskan pengecekan pada perubahan transaksi, hapus/restore, dan perubahan status review.",
                );
            }

            return $this->insight(
                'Informasi dari Zakky',
                'info',
                "Aktivitas hari ini masih dalam pola normal dengan {$logs->count()} catatan audit.",
            );
        });
    }

    /**
     * @param array<string, int> $overview
     * @return array{label: string, tone: string, message: string, items: array<int, string>, generated: bool}
     */
    public function anomalyListInsight(array $overview): array
    {
        $cacheKey = 'zakky:anomaly-list:' . md5(json_encode($overview));

        return cache()->remember($cacheKey, 600, function () use ($overview) {
            $pending = (int) ($overview['pendingReviewGroups'] ?? 0);
            $followUp = (int) ($overview['followUpGroups'] ?? 0);
            $warning = (int) ($overview['warningGroups'] ?? 0);

            $receiptFlagCount = $this->countActiveFlag(TransactionRiskReview::FLAG_UPDATED_AFTER_RECEIPT_PRINTED);

            if ($pending === 0 && $followUp === 0) {
                return $this->insight(
                    'Informasi dari Zakky',
                    'success',
                    'Tidak ada anomali aktif yang perlu ditinjau saat ini.',
                );
            }

            if ($receiptFlagCount > 0) {
                return $this->insight(
                    'Peringatan dari Zakky',
                    'warning',
                    "Ada {$pending} kasus belum ditinjau. Prioritaskan transaksi yang berubah setelah kwitansi dicetak karena dapat memengaruhi validitas bukti transaksi.",
                    [
                        "{$receiptFlagCount} kasus terkait perubahan setelah kwitansi dicetak.",
                        "{$warning} kasus berada pada level warning.",
                    ],
                );
            }

            return $this->insight(
                'Perhatian dari Zakky',
                'attention',
                "Ada {$pending} kasus belum ditinjau dan {$followUp} kasus perlu tindak lanjut. Buka detail kasus untuk memastikan alasan review sebelum ditutup aman.",
            );
        });
    }

    /**
     * @param array<string, mixed> $riskReview
     * @param array<string, mixed> $riskMeta
     * @return array{label: string, tone: string, message: string, items: array<int, string>, generated: bool}
     */
    public function anomalyDetailInsight(array $riskReview, array $riskMeta): array
    {
        $flags = collect($riskMeta['flag_keys'] ?? []);
        $reviewStatus = $riskReview['review_status'] ?? null;

        if ($flags->contains(TransactionRiskReview::FLAG_UPDATED_AFTER_RECEIPT_PRINTED)) {
            return $this->insight(
                'Catatan dari Zakky',
                'warning',
                'Transaksi ini perlu dicek ulang karena terdapat perubahan setelah kwitansi dicetak. Cocokkan nominal akhir dengan bukti pembayaran dan histori perubahan sebelum menandai transaksi sebagai aman.',
            );
        }

        if ($flags->contains(TransactionRiskReview::FLAG_SIGNIFICANT_NOMINAL_CHANGE)) {
            return $this->insight(
                'Catatan dari Zakky',
                'attention',
                'Transaksi ini memiliki perubahan nominal signifikan. Bandingkan nilai lama dan baru, lalu pastikan perubahan sesuai kebutuhan pencatatan di lapangan.',
            );
        }

        if ($flags->intersect([
            TransactionRiskReview::FLAG_EXACT_DUPLICATE,
            TransactionRiskReview::FLAG_TRANSFER_DUPLICATE_CANDIDATE,
            TransactionRiskReview::FLAG_PAYER_MATCH_SAME_BENEFICIARY,
            TransactionRiskReview::FLAG_PAYER_MATCH_DIFFERENT_BENEFICIARY,
        ])->isNotEmpty()) {
            return $this->insight(
                'Catatan dari Zakky',
                'attention',
                'Sistem menemukan pola transaksi yang mirip. Bandingkan pembayar, muzakki, nominal, dan waktu transaksi sebelum menutup review.',
            );
        }

        if ($reviewStatus === TransactionRiskReview::REVIEW_AMAN) {
            return $this->insight(
                'Informasi dari Zakky',
                'success',
                'Kasus ini sudah ditandai aman. Catatan review tetap bisa dipakai sebagai jejak keputusan admin.',
            );
        }

        return $this->insight(
            'Informasi dari Zakky',
            'info',
            'Gunakan sinyal sistem sebagai bahan pemeriksaan. Keputusan akhir tetap ditentukan dari bukti transaksi dan catatan operator.',
        );
    }

    private function countActiveFlag(string $flag): int
    {
        return TransactionRiskReview::query()
            ->join('zakat_transactions', 'zakat_transactions.id', '=', 'transaction_risk_reviews.zakat_transaction_id')
            ->whereNull('zakat_transactions.deleted_at')
            ->where('transaction_risk_reviews.risk_level', TransactionRiskReview::LEVEL_WARNING)
            ->where('transaction_risk_reviews.review_status', '!=', TransactionRiskReview::REVIEW_AMAN)
            ->where('transaction_risk_reviews.risk_flags', 'like', '%"' . $flag . '"%')
            ->distinct()
            ->count('transaction_risk_reviews.group_no_transaksi');
    }

    /**
     * @param array<int, string> $items
     * @return array{label: string, tone: string, message: string, items: array<int, string>, generated: bool}
     */
    private function insight(string $label, string $tone, string $message, array $items = []): array
    {
        return [
            'label' => $label,
            'tone' => $tone,
            'message' => $message,
            'items' => $items,
            'generated' => false,
        ];
    }
}
