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
            $table->json('anomaly_context')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('zakat_transactions', function (Blueprint $table) {
            $table->dropColumn('anomaly_context');
        });
    }
};
