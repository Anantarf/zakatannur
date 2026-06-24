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
            $table->string('question_md5', 32)->nullable()->after('question');
            $table->unique(['session_id', 'question_md5'], 'ai_chat_logs_session_md5_unique');
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
            $table->dropUnique('ai_chat_logs_session_md5_unique');
            $table->dropColumn('question_md5');
        });
    }
};
