<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('environments', function (Blueprint $table): void {
            if (! Schema::hasColumn('environments', 'auth_profile_id')) {
                $table->foreignId('auth_profile_id')->nullable()->after('base_url')->constrained('auth_profiles')->nullOnDelete();
            }
        });

        Schema::table('scan_results', function (Blueprint $table): void {
            if (! Schema::hasColumn('scan_results', 'auth_profile_id')) {
                $table->foreignId('auth_profile_id')->nullable()->after('endpoint_id')->constrained('auth_profiles')->nullOnDelete();
            }
            if (! Schema::hasColumn('scan_results', 'auth_applied')) {
                $table->boolean('auth_applied')->default(false)->after('auth_profile_id');
            }
            if (! Schema::hasColumn('scan_results', 'auth_summary')) {
                $table->string('auth_summary', 255)->nullable()->after('auth_applied');
            }
        });
    }

    public function down(): void
    {
        Schema::table('scan_results', function (Blueprint $table): void {
            if (Schema::hasColumn('scan_results', 'auth_profile_id')) {
                $table->dropConstrainedForeignId('auth_profile_id');
            }
            if (Schema::hasColumn('scan_results', 'auth_applied')) {
                $table->dropColumn('auth_applied');
            }
            if (Schema::hasColumn('scan_results', 'auth_summary')) {
                $table->dropColumn('auth_summary');
            }
        });

        Schema::table('environments', function (Blueprint $table): void {
            if (Schema::hasColumn('environments', 'auth_profile_id')) {
                $table->dropConstrainedForeignId('auth_profile_id');
            }
        });
    }
};
