<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Try each alteration separately so if one fails (e.g. index already exists/gone), the others still run.
        try {
            Schema::table('zakat_transactions', function (Blueprint $table) {
                $table->dropUnique(['no_transaksi']);
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('zakat_transactions', function (Blueprint $table) {
                $table->index('no_transaksi');
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('zakat_transactions', function (Blueprint $table) {
                $table->index('pembayar_nama');
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('zakat_transactions', function (Blueprint $table) {
                $table->index('waktu_terima');
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('zakat_transactions', function (Blueprint $table) {
                $table->index('created_at');
            });
        } catch (\Exception $e) {}
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {}
};
