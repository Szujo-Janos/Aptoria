<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('findings')) {
            Schema::table('findings', function (Blueprint $table): void {
                if (! Schema::hasColumn('findings', 'merged_into_finding_id')) {
                    $table->foreignId('merged_into_finding_id')->nullable()->after('retest_evidence_id')->constrained('findings')->nullOnDelete();
                }
                if (! Schema::hasColumn('findings', 'duplicate_group_key')) {
                    $table->string('duplicate_group_key', 160)->nullable()->after('merged_into_finding_id');
                }
                if (! Schema::hasColumn('findings', 'merged_at')) {
                    $table->timestamp('merged_at')->nullable()->after('duplicate_group_key');
                }
                if (! Schema::hasColumn('findings', 'merged_by_user_id')) {
                    $table->foreignId('merged_by_user_id')->nullable()->after('merged_at')->constrained('users')->nullOnDelete();
                }
            });
        }

        if (! Schema::hasTable('finding_duplicate_candidates')) {
            Schema::create('finding_duplicate_candidates', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('project_id')->constrained()->cascadeOnDelete();
                $table->foreignId('primary_finding_id')->constrained('findings')->cascadeOnDelete();
                $table->foreignId('duplicate_finding_id')->constrained('findings')->cascadeOnDelete();
                $table->unsignedTinyInteger('score')->default(0);
                $table->string('status', 40)->default('candidate');
                $table->json('signals_json')->nullable();
                $table->timestamp('detected_at')->nullable();
                $table->timestamp('merged_at')->nullable();
                $table->timestamps();
                $table->unique(['project_id', 'primary_finding_id', 'duplicate_finding_id'], 'finding_duplicate_unique');
                $table->index(['project_id', 'status']);
                $table->index(['project_id', 'score']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('finding_duplicate_candidates');
        if (Schema::hasTable('findings')) {
            Schema::table('findings', function (Blueprint $table): void {
                foreach (['merged_by_user_id', 'merged_at', 'duplicate_group_key', 'merged_into_finding_id'] as $column) {
                    if (Schema::hasColumn('findings', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
