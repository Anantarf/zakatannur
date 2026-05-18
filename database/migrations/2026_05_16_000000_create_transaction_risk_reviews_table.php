<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_risk_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zakat_transaction_id')->constrained('zakat_transactions')->cascadeOnDelete();
            $table->string('group_no_transaksi', 30)->index();
            $table->string('risk_level', 20)->index();
            $table->unsignedInteger('risk_score')->default(0);
            $table->json('risk_flags')->nullable();
            $table->json('reasons')->nullable();
            $table->json('duplicate_candidates')->nullable();
            $table->string('detector_version', 20)->default('v1');
            $table->string('review_status', 30)->default('belum_ditinjau')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();

            $table->unique('zakat_transaction_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_risk_reviews');
    }
};
