<?php

use App\Models\TransactionRiskReview;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    private const OBSOLETE_FLAGS = [
        'suspicious_small_amount',
        'irregular_amount',
    ];

    private const OBSOLETE_REASON_SNIPPETS = [
        'Nominal uang di bawah Rp 1.000',
        'Transaksi memiliki angka tidak bulat',
    ];

    public function up(): void
    {
        TransactionRiskReview::query()
            ->where(function ($query) {
                foreach (self::OBSOLETE_FLAGS as $flag) {
                    $query->orWhere('risk_flags', 'like', '%"' . $flag . '"%');
                }
            })
            ->chunkById(200, function ($reviews): void {
                foreach ($reviews as $review) {
                    $originalFlags = collect($review->risk_flags ?? []);
                    $cleanFlags = $originalFlags
                        ->reject(fn ($flag) => in_array($flag, self::OBSOLETE_FLAGS, true))
                        ->values();

                    $removedCount = $originalFlags->count() - $cleanFlags->count();
                    $cleanReasons = collect($review->reasons ?? [])
                        ->reject(function ($reason): bool {
                            foreach (self::OBSOLETE_REASON_SNIPPETS as $snippet) {
                                if (str_contains((string) $reason, $snippet)) {
                                    return true;
                                }
                            }

                            return false;
                        })
                        ->values();

                    $newScore = max(0, (int) $review->risk_score - ($removedCount * 20));

                    $review->forceFill([
                        'risk_flags' => $cleanFlags->all(),
                        'reasons' => $cleanReasons->all(),
                        'risk_score' => $newScore,
                        'risk_level' => $newScore >= 20
                            ? TransactionRiskReview::LEVEL_WARNING
                            : TransactionRiskReview::LEVEL_SAFE,
                    ])->save();
                }
            });
    }

    public function down(): void
    {
        //
    }
};
