<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_audit_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('generated_by')->constrained('users')->restrictOnDelete();
            $table->date('date_from');
            $table->date('date_to');
            $table->unsignedInteger('total_activities')->default(0);
            $table->unsignedInteger('sensitive_activities_count')->default(0);
            $table->text('summary');
            $table->text('recommendation');
            $table->json('context_snapshot')->nullable();
            $table->timestamps();

            $table->index(['date_from', 'date_to']);
            $table->index('generated_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_audit_summaries');
    }
};
