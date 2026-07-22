<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_chat_logs', function (Blueprint $table) {
            $table->string('model')->nullable()->after('confidence_source');
            $table->unsignedInteger('prompt_tokens')->nullable()->after('model');
            $table->unsignedInteger('completion_tokens')->nullable()->after('prompt_tokens');
            $table->unsignedInteger('total_tokens')->nullable()->after('completion_tokens');
            $table->decimal('estimated_cost_usd', 12, 8)->nullable()->after('total_tokens');
        });
    }

    public function down(): void
    {
        Schema::table('ai_chat_logs', function (Blueprint $table) {
            $table->dropColumn([
                'model',
                'prompt_tokens',
                'completion_tokens',
                'total_tokens',
                'estimated_cost_usd',
            ]);
        });
    }
};
