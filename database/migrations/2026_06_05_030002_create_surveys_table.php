<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surveys', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('version')->default('1.0');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('survey_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_id')->constrained()->cascadeOnDelete();
            $table->string('code', 5); // A, B, C, ...
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['survey_id', 'code']);
            $table->index(['survey_id', 'sort_order']);
        });

        Schema::create('survey_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_section_id')->constrained()->cascadeOnDelete();
            $table->string('question_number', 10);
            $table->text('question_text');
            $table->enum('question_type', [
                'text', 'long_text', 'number', 'money', 'date',
                'single_choice', 'multiple_choice', 'matrix', 'table_repeater', 'file_upload'
            ]);
            $table->boolean('is_required')->default(false);
            $table->boolean('allow_multiple')->default(false);
            $table->boolean('dashboard_enabled')->default(false);
            $table->string('default_chart_type')->default('bar');
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->jsonb('options')->nullable(); // Extra config per question type
            $table->timestamps();

            $table->unique(['survey_section_id', 'question_number']);
            $table->index(['survey_section_id', 'sort_order']);
            $table->index('dashboard_enabled');
        });

        Schema::create('survey_question_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained('survey_questions')->cascadeOnDelete();
            $table->string('option_label');
            $table->string('option_value');
            $table->decimal('score', 8, 2)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['question_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_question_options');
        Schema::dropIfExists('survey_questions');
        Schema::dropIfExists('survey_sections');
        Schema::dropIfExists('surveys');
    }
};
