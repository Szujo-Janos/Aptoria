<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('endpoint_snapshot_compares', function (Blueprint $table): void {
            if (! Schema::hasColumn('endpoint_snapshot_compares', 'regression_finding_count')) {
                $table->unsignedInteger('regression_finding_count')->default(0)->after('improved_count');
            }
            if (! Schema::hasColumn('endpoint_snapshot_compares', 'regression_findings_generated_at')) {
                $table->timestamp('regression_findings_generated_at')->nullable()->after('regression_finding_count');
            }
            if (! Schema::hasColumn('endpoint_snapshot_compares', 'regression_finding_summary_json')) {
                $table->json('regression_finding_summary_json')->nullable()->after('regression_findings_generated_at');
            }
        });

        Schema::table('findings', function (Blueprint $table): void {
            if (! Schema::hasColumn('findings', 'endpoint_snapshot_compare_id')) {
                $table->foreignId('endpoint_snapshot_compare_id')->nullable()->after('scan_result_id')->constrained('endpoint_snapshot_compares')->nullOnDelete();
            }
            if (! Schema::hasColumn('findings', 'endpoint_snapshot_compare_item_id')) {
                $table->foreignId('endpoint_snapshot_compare_item_id')->nullable()->after('endpoint_snapshot_compare_id')->constrained('endpoint_snapshot_compare_items')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('findings', function (Blueprint $table): void {
            if (Schema::hasColumn('findings', 'endpoint_snapshot_compare_item_id')) {
                $table->dropConstrainedForeignId('endpoint_snapshot_compare_item_id');
            }
            if (Schema::hasColumn('findings', 'endpoint_snapshot_compare_id')) {
                $table->dropConstrainedForeignId('endpoint_snapshot_compare_id');
            }
        });

        Schema::table('endpoint_snapshot_compares', function (Blueprint $table): void {
            foreach (['regression_finding_summary_json', 'regression_findings_generated_at', 'regression_finding_count'] as $column) {
                if (Schema::hasColumn('endpoint_snapshot_compares', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
