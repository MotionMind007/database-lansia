<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('export_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('disk', 64)->default('local');
            $table->string('path');
            $table->unsignedInteger('row_count')->default(0);
            $table->string('status', 32)->default('ready');
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('last_downloaded_at')->nullable();
            $table->timestamp('file_deleted_at')->nullable();
            $table->timestamps();

            $table->unique(['disk', 'path']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('export_files');
    }
};
