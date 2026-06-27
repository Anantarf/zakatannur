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
            $table->timestamp('effective_time')->virtualAs('COALESCE(waktu_terima, created_at)')->after('waktu_terima');
            $table->index('effective_time');
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
            $table->dropIndex(['effective_time']);
            $table->dropColumn('effective_time');
        });
    }
};
