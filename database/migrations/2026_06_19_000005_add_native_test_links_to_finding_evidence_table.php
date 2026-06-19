<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('finding_evidence')) {
            return;
        }

        Schema::table('finding_evidence', function (Blueprint $table): void {
            if (! Schema::hasColumn('finding_evidence', 'test_case_id')) {
                $table->foreignId('test_case_id')->nullable()->after('scan_result_id')->constrained('test_cases')->nullOnDelete();
            }
            if (! Schema::hasColumn('finding_evidence', 'test_run_id')) {
                $table->foreignId('test_run_id')->nullable()->after('test_case_id')->constrained('test_runs')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('finding_evidence')) {
            return;
        }

        Schema::table('finding_evidence', function (Blueprint $table): void {
            if (Schema::hasColumn('finding_evidence', 'test_run_id')) {
                $table->dropConstrainedForeignId('test_run_id');
            }
            if (Schema::hasColumn('finding_evidence', 'test_case_id')) {
                $table->dropConstrainedForeignId('test_case_id');
            }
        });
    }
};
