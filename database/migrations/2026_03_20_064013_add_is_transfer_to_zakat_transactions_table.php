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
        Schema::table('zakat_transactions', function (Blueprint $table) {
            $table->boolean('is_transfer')->default(false)->after('keterangan');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('zakat_transactions', function (Blueprint $table) {
            $table->dropColumn('is_transfer');
        });
    }
};
