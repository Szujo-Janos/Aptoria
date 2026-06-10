<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scan_results', function (Blueprint $table): void {
            $table->boolean('broken_auth_detected')->default(false)->after('sensitive_data_summary_json');
            $table->json('broken_auth_summary_json')->nullable()->after('broken_auth_detected');
        });
    }

    public function down(): void
    {
        Schema::table('scan_results', function (Blueprint $table): void {
            $table->dropColumn(['broken_auth_detected', 'broken_auth_summary_json']);
        });
    }
};
