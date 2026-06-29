<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_chat_logs', function (Blueprint $table) {
            $table->string('sentiment')->nullable()->default(null)->after('source_type');
            $table->index('sentiment');
        });
    }

    public function down(): void
    {
        Schema::table('ai_chat_logs', function (Blueprint $table) {
            $table->dropIndex(['sentiment']);
            $table->dropColumn('sentiment');
        });
    }
};
