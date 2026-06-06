<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->upSqliteCompatible();

            return;
        }

        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');

        DB::statement('CREATE INDEX IF NOT EXISTS idx_survey_responses_created_at_desc ON survey_responses (created_at DESC)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_survey_responses_status_submitted_at ON survey_responses (status, submitted_at)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_survey_responses_status_created_at ON survey_responses (status, created_at DESC)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_survey_responses_region_created_at ON survey_responses (region_id, created_at DESC)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_survey_responses_surveyor_created_at ON survey_responses (surveyor_id, created_at DESC)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_survey_responses_questionnaire_trgm ON survey_responses USING gin (questionnaire_number gin_trgm_ops)');

        DB::statement('CREATE INDEX IF NOT EXISTS idx_respondents_full_name_trgm ON respondents USING gin (full_name gin_trgm_ops)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_respondents_gender_id ON respondents (gender, id)');

        DB::statement('CREATE INDEX IF NOT EXISTS idx_regions_parent_type_name ON regions (parent_id, type, name)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_regions_active_type_name ON regions (is_active, type, name)');

        DB::statement('CREATE INDEX IF NOT EXISTS idx_verification_logs_response_verified_at_desc ON verification_logs (survey_response_id, verified_at DESC)');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->downSqliteCompatible();

            return;
        }

        DB::statement('DROP INDEX IF EXISTS idx_verification_logs_response_verified_at_desc');
        DB::statement('DROP INDEX IF EXISTS idx_regions_active_type_name');
        DB::statement('DROP INDEX IF EXISTS idx_regions_parent_type_name');
        DB::statement('DROP INDEX IF EXISTS idx_respondents_gender_id');
        DB::statement('DROP INDEX IF EXISTS idx_respondents_full_name_trgm');
        DB::statement('DROP INDEX IF EXISTS idx_survey_responses_questionnaire_trgm');
        DB::statement('DROP INDEX IF EXISTS idx_survey_responses_surveyor_created_at');
        DB::statement('DROP INDEX IF EXISTS idx_survey_responses_region_created_at');
        DB::statement('DROP INDEX IF EXISTS idx_survey_responses_status_created_at');
        DB::statement('DROP INDEX IF EXISTS idx_survey_responses_status_submitted_at');
        DB::statement('DROP INDEX IF EXISTS idx_survey_responses_created_at_desc');
    }

    private function upSqliteCompatible(): void
    {
        DB::statement('CREATE INDEX IF NOT EXISTS idx_survey_responses_created_at_desc ON survey_responses (created_at)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_survey_responses_status_submitted_at ON survey_responses (status, submitted_at)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_survey_responses_status_created_at ON survey_responses (status, created_at)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_survey_responses_region_created_at ON survey_responses (region_id, created_at)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_survey_responses_surveyor_created_at ON survey_responses (surveyor_id, created_at)');

        DB::statement('CREATE INDEX IF NOT EXISTS idx_respondents_gender_id ON respondents (gender, id)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_regions_parent_type_name ON regions (parent_id, type, name)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_regions_active_type_name ON regions (is_active, type, name)');
    }

    private function downSqliteCompatible(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_regions_active_type_name');
        DB::statement('DROP INDEX IF EXISTS idx_regions_parent_type_name');
        DB::statement('DROP INDEX IF EXISTS idx_respondents_gender_id');
        DB::statement('DROP INDEX IF EXISTS idx_survey_responses_surveyor_created_at');
        DB::statement('DROP INDEX IF EXISTS idx_survey_responses_region_created_at');
        DB::statement('DROP INDEX IF EXISTS idx_survey_responses_status_created_at');
        DB::statement('DROP INDEX IF EXISTS idx_survey_responses_status_submitted_at');
        DB::statement('DROP INDEX IF EXISTS idx_survey_responses_created_at_desc');
    }
};
