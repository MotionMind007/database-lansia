<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            // Prevent duplicate bulk JSON answers per response
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS idx_survey_answers_one_bulk_per_response ON survey_answers (survey_response_id) WHERE question_id IS NULL');

            // Soft-delete-aware indexes for common queries
            DB::statement('CREATE INDEX IF NOT EXISTS idx_survey_responses_active_created ON survey_responses (created_at DESC) WHERE deleted_at IS NULL');
            DB::statement('CREATE INDEX IF NOT EXISTS idx_survey_responses_active_status ON survey_responses (status, submitted_at) WHERE deleted_at IS NULL');
            DB::statement('CREATE INDEX IF NOT EXISTS idx_survey_responses_active_surveyor ON survey_responses (surveyor_id, created_at DESC) WHERE deleted_at IS NULL');
            DB::statement('CREATE INDEX IF NOT EXISTS idx_respondents_active_nik ON respondents (nik) WHERE deleted_at IS NULL AND nik IS NOT NULL');
        }

        if (DB::getDriverName() === 'sqlite') {
            // SQLite doesn't support partial indexes well, but unique index on expression works
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS idx_survey_answers_one_bulk_per_response ON survey_answers (survey_response_id) WHERE question_id IS NULL');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS idx_respondents_active_nik');
            DB::statement('DROP INDEX IF EXISTS idx_survey_responses_active_surveyor');
            DB::statement('DROP INDEX IF EXISTS idx_survey_responses_active_status');
            DB::statement('DROP INDEX IF EXISTS idx_survey_responses_active_created');
            DB::statement('DROP INDEX IF EXISTS idx_survey_answers_one_bulk_per_response');
        }

        if (DB::getDriverName() === 'sqlite') {
            DB::statement('DROP INDEX IF EXISTS idx_survey_answers_one_bulk_per_response');
        }
    }
};
