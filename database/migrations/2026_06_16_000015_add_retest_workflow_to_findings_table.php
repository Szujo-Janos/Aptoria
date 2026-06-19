<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('findings')) {
            return;
        }

        Schema::table('findings', function (Blueprint $table): void {
            if (! Schema::hasColumn('findings', 'retest_status')) {
                $table->string('retest_status', 40)->default('not_required')->after('retest_required');
            }
            if (! Schema::hasColumn('findings', 'retest_note')) {
                $table->text('retest_note')->nullable()->after('retest_status');
            }
            if (! Schema::hasColumn('findings', 'retest_requested_at')) {
                $table->timestamp('retest_requested_at')->nullable()->after('retest_note');
            }
            if (! Schema::hasColumn('findings', 'ready_for_retest_at')) {
                $table->timestamp('ready_for_retest_at')->nullable()->after('retest_requested_at');
            }
            if (! Schema::hasColumn('findings', 'retested_at')) {
                $table->timestamp('retested_at')->nullable()->after('ready_for_retest_at');
            }
            if (! Schema::hasColumn('findings', 'retested_by_user_id')) {
                $table->unsignedBigInteger('retested_by_user_id')->nullable()->after('retested_at');
            }
            if (! Schema::hasColumn('findings', 'retest_evidence_id')) {
                $table->unsignedBigInteger('retest_evidence_id')->nullable()->after('retested_by_user_id');
            }
        });

        DB::table('findings')
            ->where('retest_required', true)
            ->where(function ($query): void {
                $query->whereNull('retest_status')->orWhere('retest_status', 'not_required');
            })
            ->update([
                'retest_status' => 'required',
                'retest_requested_at' => now(),
            ]);

        Schema::table('findings', function (Blueprint $table): void {
            try {
                $table->index(['project_id', 'retest_status'], 'findings_project_retest_status_index');
            } catch (Throwable) {
                // SQLite/test environments may already have the index after repeated hotfix runs.
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('findings')) {
            return;
        }

        Schema::table('findings', function (Blueprint $table): void {
            foreach (['retest_evidence_id', 'retested_by_user_id', 'retested_at', 'ready_for_retest_at', 'retest_requested_at', 'retest_note', 'retest_status'] as $column) {
                if (Schema::hasColumn('findings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
