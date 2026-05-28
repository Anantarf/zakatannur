<?php

use App\Models\TransactionRiskReview;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        TransactionRiskReview::query()
            ->where('risk_flags', 'like', '%"infaq_outlier"%')
            ->chunkById(200, function ($reviews): void {
                foreach ($reviews as $review) {
                    $originalFlags = collect($review->risk_flags ?? []);
                    $cleanFlags = $originalFlags
                        ->reject(fn ($flag) => $flag === 'infaq_outlier')
                        ->values();

                    $removedCount = $originalFlags->count() - $cleanFlags->count();
                    $cleanReasons = collect($review->reasons ?? [])
                        ->reject(fn ($reason) => str_contains((string) $reason, 'Nominal infaq Rp'))
                        ->values();

                    $newScore = max(0, (int) $review->risk_score - ($removedCount * 20));

                    $review->forceFill([
                        'risk_flags' => $cleanFlags->all(),
                        'reasons' => $cleanReasons->all(),
                        'risk_score' => $newScore,
                        'risk_level' => $newScore >= 20
                            ? TransactionRiskReview::LEVEL_WARNING
                            : TransactionRiskReview::LEVEL_NORMAL,
                    ])->save();
                }
            });
    }

    public function down(): void
    {
        //
    }
};
