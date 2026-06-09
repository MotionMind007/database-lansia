<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('dashboard_widgets');
    }

    public function down(): void
    {
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
};
