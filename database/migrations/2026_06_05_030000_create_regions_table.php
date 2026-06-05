<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('regions')->nullOnDelete();
            $table->string('name');
            $table->enum('type', ['province', 'city', 'district', 'village']);
            $table->string('code')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['parent_id', 'type']);
            $table->index(['type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regions');
    }
};
