<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dashboard_answer_facts', function (Blueprint $table) {
            $table->index(['question_key', 'row_label', 'column_label'], 'idx_dashboard_facts_question_grouping');
            $table->index(['question_key', 'column_label', 'survey_response_id'], 'idx_dashboard_facts_question_response');
            $table->index(['gender', 'question_key', 'row_label', 'column_label'], 'idx_dashboard_facts_gender_question_grouping');
            $table->index(['city_id', 'gender', 'question_key', 'row_label'], 'idx_dashboard_facts_city_gender_question_row');
            $table->index(['district_id', 'gender', 'question_key', 'row_label'], 'idx_dashboard_facts_district_gender_question_row');
            $table->index(['region_id', 'gender', 'question_key', 'row_label'], 'idx_dashboard_facts_region_gender_question_row');
        });
    }

    public function down(): void
    {
        Schema::table('dashboard_answer_facts', function (Blueprint $table) {
            $table->dropIndex('idx_dashboard_facts_question_grouping');
            $table->dropIndex('idx_dashboard_facts_question_response');
            $table->dropIndex('idx_dashboard_facts_gender_question_grouping');
            $table->dropIndex('idx_dashboard_facts_city_gender_question_row');
            $table->dropIndex('idx_dashboard_facts_district_gender_question_row');
            $table->dropIndex('idx_dashboard_facts_region_gender_question_row');
        });
    }
};
