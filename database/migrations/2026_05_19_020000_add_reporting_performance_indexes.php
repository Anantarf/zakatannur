<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('zakat_transactions', function (Blueprint $table) {
            $table->index(['zakat_period_id', 'status', 'waktu_terima', 'created_at', 'no_transaksi'], 'zakat_tx_period_status_time_group_idx');
            $table->index(['tahun_zakat', 'status', 'waktu_terima', 'created_at', 'no_transaksi'], 'zakat_tx_year_status_time_group_idx');
            $table->index(['muzakki_id', 'zakat_period_id', 'waktu_terima'], 'zakat_tx_muzakki_period_time_idx');
            $table->index(['no_transaksi', 'deleted_at'], 'zakat_tx_group_deleted_idx');
        });

        Schema::table('transaction_risk_reviews', function (Blueprint $table) {
            $table->index(['group_no_transaksi', 'risk_level', 'review_status'], 'risk_reviews_group_level_status_idx');
            $table->index(['review_status', 'risk_level', 'group_no_transaksi'], 'risk_reviews_status_level_group_idx');
        });
    }

    public function down(): void
    {
        Schema::table('transaction_risk_reviews', function (Blueprint $table) {
            $table->dropIndex('risk_reviews_status_level_group_idx');
            $table->dropIndex('risk_reviews_group_level_status_idx');
        });

        Schema::table('zakat_transactions', function (Blueprint $table) {
            $table->dropIndex('zakat_tx_group_deleted_idx');
            $table->dropIndex('zakat_tx_muzakki_period_time_idx');
            $table->dropIndex('zakat_tx_year_status_time_group_idx');
            $table->dropIndex('zakat_tx_period_status_time_group_idx');
        });
    }
};
