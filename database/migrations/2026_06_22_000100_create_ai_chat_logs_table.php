<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_chat_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('session_id', 80)->nullable();
            $table->text('question');
            $table->string('intent', 80)->nullable();
            $table->text('context_summary')->nullable();
            $table->text('answer');
            $table->string('source_type', 40)->nullable();
            $table->timestamps();

            $table->index(['session_id', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_chat_logs');
    }
};
