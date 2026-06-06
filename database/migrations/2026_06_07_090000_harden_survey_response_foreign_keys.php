<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('survey_responses', function (Blueprint $table) {
            $table->dropForeign(['survey_id']);
            $table->dropForeign(['respondent_id']);
            $table->dropForeign(['surveyor_id']);
            $table->dropForeign(['region_id']);
        });

        Schema::table('survey_responses', function (Blueprint $table) {
            $table->foreign('survey_id')->references('id')->on('surveys')->restrictOnDelete();
            $table->foreign('respondent_id')->references('id')->on('respondents')->restrictOnDelete();
            $table->foreign('surveyor_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('region_id')->references('id')->on('regions')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('survey_responses', function (Blueprint $table) {
            $table->dropForeign(['survey_id']);
            $table->dropForeign(['respondent_id']);
            $table->dropForeign(['surveyor_id']);
            $table->dropForeign(['region_id']);
        });

        Schema::table('survey_responses', function (Blueprint $table) {
            $table->foreign('survey_id')->references('id')->on('surveys')->cascadeOnDelete();
            $table->foreign('respondent_id')->references('id')->on('respondents')->cascadeOnDelete();
            $table->foreign('surveyor_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('region_id')->references('id')->on('regions')->cascadeOnDelete();
        });
    }
};
