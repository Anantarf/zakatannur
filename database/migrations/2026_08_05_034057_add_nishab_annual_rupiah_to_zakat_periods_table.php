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
        Schema::table('zakat_periods', function (Blueprint $table) {
            $table->unsignedBigInteger('nishab_annual_rupiah')->nullable()->after('gold_price_per_gram');
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
            $table->dropColumn('nishab_annual_rupiah');
        });
    }
};
