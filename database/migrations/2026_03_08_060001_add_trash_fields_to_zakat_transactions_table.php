<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('zakat_transactions', function (Blueprint $table) {
            $table->softDeletes();
            $table->foreignId('deleted_by')->nullable()->after('deleted_at')->constrained('users');
            $table->text('deleted_reason')->nullable()->after('deleted_by');

            $table->timestamp('restored_at')->nullable()->after('deleted_reason');
            $table->foreignId('restored_by')->nullable()->after('restored_at')->constrained('users');

            $table->index(['deleted_at']);
        });
    }

    public function down(): void
    {
        Schema::table('zakat_transactions', function (Blueprint $table) {
            $table->dropIndex(['deleted_at']);

            $table->dropConstrainedForeignId('restored_by');
            $table->dropColumn('restored_at');

            $table->dropColumn('deleted_reason');
            $table->dropConstrainedForeignId('deleted_by');
            $table->dropSoftDeletes();
        });
    }
};
