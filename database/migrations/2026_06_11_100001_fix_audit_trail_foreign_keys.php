<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return; // SQLite cannot alter FK constraints
        }

        // verification_logs.verified_by: cascade → nullOnDelete (preserve audit trail)
        Schema::table('verification_logs', function (Blueprint $table) {
            $table->dropForeign(['verified_by']);
        });
        Schema::table('verification_logs', function (Blueprint $table) {
            $table->foreignId('verified_by')->nullable()->change();
            $table->foreign('verified_by')->references('id')->on('users')->nullOnDelete();
        });

        // respondent_documents.uploaded_by: cascade → nullOnDelete (preserve document metadata)
        Schema::table('respondent_documents', function (Blueprint $table) {
            $table->dropForeign(['uploaded_by']);
        });
        Schema::table('respondent_documents', function (Blueprint $table) {
            $table->foreignId('uploaded_by')->nullable()->change();
            $table->foreign('uploaded_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('verification_logs', function (Blueprint $table) {
            $table->dropForeign(['verified_by']);
        });
        Schema::table('verification_logs', function (Blueprint $table) {
            $table->foreign('verified_by')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('respondent_documents', function (Blueprint $table) {
            $table->dropForeign(['uploaded_by']);
        });
        Schema::table('respondent_documents', function (Blueprint $table) {
            $table->foreign('uploaded_by')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
