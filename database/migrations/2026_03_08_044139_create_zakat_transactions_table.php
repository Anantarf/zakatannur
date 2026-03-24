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
        Schema::create('zakat_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('no_transaksi', 30);
            $table->foreignId('muzakki_id')->constrained('muzakki');
            $table->string('category', 10);
            $table->unsignedInteger('tahun_zakat');
            $table->string('metode', 10);
            $table->unsignedBigInteger('nominal_uang')->nullable();
            $table->decimal('jumlah_beras_kg', 10, 2)->nullable();
            $table->unsignedInteger('jiwa')->nullable();
            $table->unsignedInteger('hari')->nullable();
            $table->boolean('is_khusus')->default(false);
            $table->unsignedInteger('default_fitrah_cash_per_jiwa_used')->nullable();
            $table->unsignedInteger('default_fidyah_per_hari_used')->nullable();
            $table->foreignId('petugas_id')->constrained('users');
            $table->text('keterangan')->nullable();
            $table->string('status', 10)->default('valid');
            $table->text('void_reason')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users');
            $table->timestamp('waktu_terima')->nullable();
            $table->timestamps();

            $table->unique('no_transaksi');
            $table->index(['tahun_zakat', 'category']);
            $table->index(['tahun_zakat', 'metode']);
            $table->index(['petugas_id', 'created_at']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('zakat_transactions');
    }
};
