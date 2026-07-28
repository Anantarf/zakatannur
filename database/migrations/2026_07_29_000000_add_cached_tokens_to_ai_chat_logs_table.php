<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * OpenAI reports prompt_tokens_details.cached_tokens whenever automatic prompt caching hits
     * (kicks in for prompts >1024 tokens, which the system prompt already exceeds - see Bab 15).
     * Persisting it turns "caching is probably happening" into a number we can actually check.
     */
    public function up(): void
    {
        Schema::table('ai_chat_logs', function (Blueprint $table) {
            $table->unsignedInteger('cached_tokens')->nullable()->after('prompt_tokens');
        });
    }

    public function down(): void
    {
        Schema::table('ai_chat_logs', function (Blueprint $table) {
            $table->dropColumn('cached_tokens');
        });
    }
};
