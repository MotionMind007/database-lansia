<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('survey_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_id')->constrained()->restrictOnDelete();
            $table->foreignId('respondent_id')->constrained()->restrictOnDelete();
            $table->string('questionnaire_number')->unique();
            $table->foreignId('surveyor_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('region_id')->constrained('regions')->restrictOnDelete();
            $table->date('interview_date');
            $table->enum('status', [
                'draft', 'submitted', 'need_revision', 'verified', 'rejected'
            ])->default('draft');
            $table->text('surveyor_notes')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Indexes kritis untuk performance dengan ratusan ribu data
            $table->index('status');
            $table->index('surveyor_id');
            $table->index('region_id');
            $table->index('interview_date');
            $table->index('questionnaire_number');
            $table->index(['status', 'surveyor_id']);
            $table->index(['status', 'region_id']);
            $table->index(['survey_id', 'status']);
            $table->index('submitted_at');
        });

        Schema::create('survey_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_response_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('survey_questions')->cascadeOnDelete();
            $table->foreignId('option_id')->nullable()->constrained('survey_question_options')->nullOnDelete();
            $table->text('answer_text')->nullable();
            $table->decimal('answer_number', 15, 2)->nullable();
            $table->jsonb('answer_json')->nullable(); // matrix, table, multiple choice
            $table->timestamps();

            // Composite indexes untuk query dashboard
            $table->index(['question_id', 'option_id']);
            $table->index(['survey_response_id', 'question_id']);
            $table->index('question_id');
        });

        Schema::create('verification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_response_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['submitted', 'need_revision', 'verified', 'rejected']);
            $table->text('note')->nullable();
            $table->foreignId('verified_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('verified_at');
            $table->timestamps();

            $table->index(['survey_response_id', 'verified_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verification_logs');
        Schema::dropIfExists('survey_answers');
        Schema::dropIfExists('survey_responses');
    }
};
