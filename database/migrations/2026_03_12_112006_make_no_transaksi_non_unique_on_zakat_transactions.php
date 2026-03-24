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
    public function up(): void
    {
        Schema::table('zakat_transactions', function (Blueprint $table) {
            $table->dropUnique(['no_transaksi']);
            // Add a regular index instead to keep lookups fast
            $table->index('no_transaksi');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('zakat_transactions', function (Blueprint $table) {
            $table->dropIndex(['no_transaksi']);
            $table->unique('no_transaksi');
        });
    }
};
