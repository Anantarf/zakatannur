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
            $table->decimal('default_fidyah_beras_per_hari', 5, 2)->default(0.75)->after('default_fidyah_per_hari');
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
            $table->dropColumn('default_fidyah_beras_per_hari');
        });
    }
};
