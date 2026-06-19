<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('report_versions')) {
            return;
        }

        Schema::table('report_versions', function (Blueprint $table): void {
            if (! Schema::hasColumn('report_versions', 'release_decision_snapshot_id')) {
                $table->unsignedBigInteger('release_decision_snapshot_id')->nullable()->after('release_readiness_run_id')->index();
            }
            if (! Schema::hasColumn('report_versions', 'reviewed_by_user_id')) {
                $table->unsignedBigInteger('reviewed_by_user_id')->nullable()->after('generated_by_user_id')->index();
            }
            if (! Schema::hasColumn('report_versions', 'approved_by_user_id')) {
                $table->unsignedBigInteger('approved_by_user_id')->nullable()->after('reviewed_by_user_id')->index();
            }
            if (! Schema::hasColumn('report_versions', 'archived_by_user_id')) {
                $table->unsignedBigInteger('archived_by_user_id')->nullable()->after('approved_by_user_id')->index();
            }
            if (! Schema::hasColumn('report_versions', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable()->after('generated_at');
            }
            if (! Schema::hasColumn('report_versions', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('reviewed_at');
            }
            if (! Schema::hasColumn('report_versions', 'archived_at')) {
                $table->timestamp('archived_at')->nullable()->after('approved_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('report_versions')) {
            return;
        }

        Schema::table('report_versions', function (Blueprint $table): void {
            foreach (['release_decision_snapshot_id', 'reviewed_by_user_id', 'approved_by_user_id', 'archived_by_user_id', 'reviewed_at', 'approved_at', 'archived_at'] as $column) {
                if (Schema::hasColumn('report_versions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
