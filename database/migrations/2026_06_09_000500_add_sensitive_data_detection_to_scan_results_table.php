<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scan_results', function (Blueprint $table): void {
            $table->boolean('sensitive_data_detected')->default(false)->after('body_preview');
            $table->unsignedSmallInteger('sensitive_data_count')->default(0)->after('sensitive_data_detected');
            $table->json('sensitive_data_summary_json')->nullable()->after('sensitive_data_count');
        });
    }

    public function down(): void
    {
        Schema::table('scan_results', function (Blueprint $table): void {
            $table->dropColumn(['sensitive_data_detected', 'sensitive_data_count', 'sensitive_data_summary_json']);
        });
    }
};
