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
        Schema::create('templates', function (Blueprint $table) {
            $table->id();
            $table->string('template_type', 30)->default('letterhead');
            $table->unsignedInteger('version')->default(1);
            $table->boolean('is_active')->default(false);
            $table->string('storage_path');
            $table->string('original_filename');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('file_size_bytes');
            $table->foreignId('uploaded_by')->constrained('users');
            $table->timestamps();

            $table->index(['template_type', 'is_active']);
            $table->unique(['template_type', 'version']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('templates');
    }
};
