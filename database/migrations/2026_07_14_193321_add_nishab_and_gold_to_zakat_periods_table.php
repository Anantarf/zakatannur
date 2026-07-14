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
        Schema::table('zakat_periods', function (Blueprint $table) {
            $table->unsignedSmallInteger('nishab_gold_gram')->default(85)->after('default_fidyah_beras_per_hari');
            $table->unsignedBigInteger('gold_price_per_gram')->default(900000)->after('nishab_gold_gram');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('zakat_periods', function (Blueprint $table) {
            $table->dropColumn(['nishab_gold_gram', 'gold_price_per_gram']);
        });
    }
};
