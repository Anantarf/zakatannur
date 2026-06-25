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
        Schema::table('ai_chat_logs', function (Blueprint $table) {
            $table->enum('confidence_source', ['knowledge', 'calculation', 'ai', 'fallback'])->nullable()->after('sentiment')->comment('Source of response: knowledge=KB match, calculation=regex calc, ai=LLM, fallback=error response');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ai_chat_logs', function (Blueprint $table) {
            $table->dropColumn('confidence_source');
        });
    }
};
