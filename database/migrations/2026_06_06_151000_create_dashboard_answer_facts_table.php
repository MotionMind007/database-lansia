<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dashboard_answer_facts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_response_id')->constrained()->cascadeOnDelete();
            $table->foreignId('respondent_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('survey_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('surveyor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained('regions')->nullOnDelete();
            $table->foreignId('district_id')->nullable()->constrained('regions')->nullOnDelete();
            $table->foreignId('region_id')->nullable()->constrained('regions')->nullOnDelete();
            $table->string('status', 32)->index();
            $table->string('gender', 16)->nullable()->index();
            $table->string('question_key', 80);
            $table->string('question_number', 20);
            $table->text('question_label');
            $table->string('question_group');
            $table->string('question_kind', 40);
            $table->string('question_display', 40);
            $table->unsignedSmallInteger('question_sort');
            $table->string('row_label');
            $table->string('column_label')->nullable();
            $table->timestamp('response_created_at')->nullable();
            $table->timestamps();

            $table->index(['question_key', 'row_label']);
            $table->index(['question_key', 'column_label']);
            $table->index(['question_group', 'question_sort']);
            $table->index(['city_id', 'gender', 'question_key']);
            $table->index(['district_id', 'gender', 'question_key']);
            $table->index(['region_id', 'gender', 'question_key']);
            $table->index(['surveyor_id', 'question_key']);
            $table->index(['status', 'question_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_answer_facts');
    }
};
