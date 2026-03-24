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
            $table->string('shift', 20)->nullable()->after('no_transaksi');
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
            $table->dropColumn('shift');
        });
    }
};
