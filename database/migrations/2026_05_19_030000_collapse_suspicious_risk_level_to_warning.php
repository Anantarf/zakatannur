<?php

use App\Models\TransactionRiskReview;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('transaction_risk_reviews')
            ->where('risk_level', TransactionRiskReview::LEVEL_SUSPICIOUS)
            ->update([
                'risk_level' => TransactionRiskReview::LEVEL_WARNING,
                'updated_at' => now(config('zakat.timezone')),
            ]);
    }

    public function down(): void
    {
        // Irreversible data cleanup: the original suspicious/warning distinction was collapsed.
        // Recreating the previous value would require historical data that this migration no longer has.
    }
};
