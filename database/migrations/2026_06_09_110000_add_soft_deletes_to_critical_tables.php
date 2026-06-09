<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('survey_responses', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('respondents', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('respondent_documents', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('survey_responses', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('respondents', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('respondent_documents', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
