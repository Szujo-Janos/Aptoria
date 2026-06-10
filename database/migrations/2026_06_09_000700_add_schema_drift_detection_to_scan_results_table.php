<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scan_results', function (Blueprint $table): void {
            $table->json('response_schema_json')->nullable()->after('body_preview');
            $table->boolean('schema_drift_detected')->default(false)->after('broken_auth_summary_json');
            $table->unsignedInteger('schema_drift_count')->default(0)->after('schema_drift_detected');
            $table->json('schema_drift_summary_json')->nullable()->after('schema_drift_count');
        });
    }

    public function down(): void
    {
        Schema::table('scan_results', function (Blueprint $table): void {
            $table->dropColumn([
                'response_schema_json',
                'schema_drift_detected',
                'schema_drift_count',
                'schema_drift_summary_json',
            ]);
        });
    }
};
