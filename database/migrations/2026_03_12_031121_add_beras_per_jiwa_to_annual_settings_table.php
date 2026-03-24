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
        Schema::table('annual_settings', function (Blueprint $table) {
            $table->decimal('default_fitrah_beras_per_jiwa', 8, 2)->default(2.5)->after('default_fitrah_cash_per_jiwa');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('annual_settings', function (Blueprint $table) {
            $table->dropColumn('default_fitrah_beras_per_jiwa');
        });
    }
};
