<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('respondents', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->enum('gender', ['male', 'female']);
            $table->unsignedSmallInteger('age');
            $table->string('education')->nullable();
            $table->string('occupation')->nullable();
            $table->text('address')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('religion')->nullable();
            $table->string('ethnicity')->nullable();
            $table->enum('citizenship_status', ['OAP', 'Non_OAP', 'WNI'])->default('WNI');
            $table->string('household_status')->nullable();
            $table->foreignId('region_id')->nullable()->constrained('regions')->nullOnDelete();
            $table->timestamps();

            // Indexes untuk filter yang sering dipakai
            $table->index('full_name');
            $table->index('gender');
            $table->index('citizenship_status');
            $table->index('region_id');
        });

        Schema::create('family_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('respondent_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->enum('gender', ['male', 'female'])->nullable();
            $table->unsignedSmallInteger('age')->nullable();
            $table->string('status')->nullable(); // hubungan dalam RT
            $table->string('education')->nullable();
            $table->string('occupation')->nullable();
            $table->enum('ktp_status', ['e_ktp', 'ktp_nasional', 'no_ktp'])->nullable();
            $table->timestamps();

            $table->index('respondent_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('family_members');
        Schema::dropIfExists('respondents');
    }
};
