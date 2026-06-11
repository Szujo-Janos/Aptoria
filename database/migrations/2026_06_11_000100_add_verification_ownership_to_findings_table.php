<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('findings', function (Blueprint $table): void {
            if (! Schema::hasColumn('findings', 'owner_user_id')) {
                $table->foreignId('owner_user_id')->nullable()->after('project_id')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('findings', 'due_date')) {
                $table->timestamp('due_date')->nullable()->after('owner_user_id');
            }
            if (! Schema::hasColumn('findings', 'priority')) {
                $table->string('priority', 30)->default('medium')->after('severity');
            }
            if (! Schema::hasColumn('findings', 'verification_status')) {
                $table->string('verification_status', 40)->default('pending')->after('status');
            }
            if (! Schema::hasColumn('findings', 'retest_required')) {
                $table->boolean('retest_required')->default(false)->after('verification_status');
            }
            if (! Schema::hasColumn('findings', 'retest_result')) {
                $table->string('retest_result', 40)->nullable()->after('retest_required');
            }
            if (! Schema::hasColumn('findings', 'fix_evidence_required')) {
                $table->boolean('fix_evidence_required')->default(false)->after('retest_result');
            }
            if (! Schema::hasColumn('findings', 'verified_by_user_id')) {
                $table->foreignId('verified_by_user_id')->nullable()->after('fix_evidence_required')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('findings', 'verified_at')) {
                $table->timestamp('verified_at')->nullable()->after('verified_by_user_id');
            }
            if (! Schema::hasColumn('findings', 'last_retest_at')) {
                $table->timestamp('last_retest_at')->nullable()->after('verified_at');
            }
            if (! Schema::hasColumn('findings', 'linked_release_gate_id')) {
                $table->foreignId('linked_release_gate_id')->nullable()->after('test_case_id')->constrained('qa_release_gates')->nullOnDelete();
            }
        });

        Schema::table('findings', function (Blueprint $table): void {
            $table->index(['project_id', 'owner_user_id'], 'findings_project_owner_idx');
            $table->index(['project_id', 'verification_status'], 'findings_project_verification_idx');
            $table->index(['project_id', 'due_date'], 'findings_project_due_date_idx');
        });
    }

    public function down(): void
    {
        Schema::table('findings', function (Blueprint $table): void {
            $table->dropIndex('findings_project_owner_idx');
            $table->dropIndex('findings_project_verification_idx');
            $table->dropIndex('findings_project_due_date_idx');
        });

        Schema::table('findings', function (Blueprint $table): void {
            if (Schema::hasColumn('findings', 'owner_user_id')) {
                $table->dropConstrainedForeignId('owner_user_id');
            }
            if (Schema::hasColumn('findings', 'verified_by_user_id')) {
                $table->dropConstrainedForeignId('verified_by_user_id');
            }
            if (Schema::hasColumn('findings', 'linked_release_gate_id')) {
                $table->dropConstrainedForeignId('linked_release_gate_id');
            }
            foreach (['due_date', 'priority', 'verification_status', 'retest_required', 'retest_result', 'fix_evidence_required', 'verified_at', 'last_retest_at'] as $column) {
                if (Schema::hasColumn('findings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
