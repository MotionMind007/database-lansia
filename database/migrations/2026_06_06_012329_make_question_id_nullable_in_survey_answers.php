<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('survey_answers', function (Blueprint $table) {
            // Allow null for bulk JSON storage approach (one record per response, no specific question)
            $table->foreignId('question_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('survey_answers', function (Blueprint $table) {
            $table->foreignId('question_id')->nullable(false)->change();
        });
    }
};
