<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->unique()->nullable()->after('name');
            $table->foreignId('region_id')->nullable()->constrained('regions')->nullOnDelete()->after('username');
            $table->boolean('is_active')->default(true)->after('region_id');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['region_id']);
            $table->dropColumn(['username', 'region_id', 'is_active', 'last_login_at']);
        });
    }
};
