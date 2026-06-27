<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::table('zakat_transactions')
            ->where('no_transaksi', 'LIKE', 'TRANS-%')
            ->update(['no_transaksi' => DB::raw("REPLACE(no_transaksi, 'TRANS-', 'TRX-')")]);
    }

    public function down()
    {
        DB::table('zakat_transactions')
            ->where('no_transaksi', 'LIKE', 'TRX-%')
            ->update(['no_transaksi' => DB::raw("REPLACE(no_transaksi, 'TRX-', 'TRANS-')")]);
    }
};
