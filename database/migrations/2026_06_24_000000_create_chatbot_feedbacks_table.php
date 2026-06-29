<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chatbot_feedbacks', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->nullable()->index();
            $table->text('message');
            $table->enum('rating', ['helpful', 'unhelpful'])->index();
            $table->ipAddress('ip_address')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chatbot_feedbacks');
    }
};
