<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('zakat_transactions', function (Blueprint $table) {
            $table->timestamp('receipt_printed_at')->nullable()->after('restored_by');
            $table->foreignId('receipt_printed_by')->nullable()->after('receipt_printed_at')->constrained('users')->nullOnDelete();
            $table->index('receipt_printed_at');
        });
    }

    public function down(): void
    {
        Schema::table('zakat_transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('receipt_printed_by');
            $table->dropIndex(['receipt_printed_at']);
            $table->dropColumn('receipt_printed_at');
        });
    }
};
