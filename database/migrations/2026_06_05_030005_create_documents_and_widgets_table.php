<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('respondent_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('respondent_id')->constrained()->cascadeOnDelete();
            $table->foreignId('survey_response_id')->constrained()->cascadeOnDelete();
            $table->enum('document_type', ['ktp', 'kk', 'surat_domisili', 'foto_rumah', 'lainnya']);
            $table->string('file_path');
            $table->string('file_name');
            $table->string('mime_type', 100);
            $table->unsignedInteger('file_size'); // bytes
            $table->text('notes')->nullable();
            $table->boolean('is_latest')->default(true);
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['respondent_id', 'document_type', 'is_latest']);
            $table->index('survey_response_id');
        });

        Schema::create('dashboard_widgets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('question_id')->constrained('survey_questions')->cascadeOnDelete();
            $table->enum('chart_type', ['number_card', 'bar', 'pie', 'line', 'table', 'matrix_table']);
            $table->boolean('is_enabled')->default(false);
            $table->unsignedSmallInteger('position')->default(0);
            $table->enum('width', ['half', 'full', 'third'])->default('half');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['is_enabled', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_widgets');
        Schema::dropIfExists('respondent_documents');
    }
};
