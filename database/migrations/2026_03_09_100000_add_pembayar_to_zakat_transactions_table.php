<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('zakat_transactions', function (Blueprint $table) {
            $table->string('pembayar_nama');
            $table->string('pembayar_alamat');
            $table->string('pembayar_phone')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('zakat_transactions', function (Blueprint $table) {
            $table->dropColumn(['pembayar_nama', 'pembayar_alamat', 'pembayar_phone']);
        });
    }
};
