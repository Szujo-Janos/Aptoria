<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('release_gates')) {
            Schema::create('release_gates', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('project_id')->constrained()->cascadeOnDelete();
                $table->foreignId('release_readiness_run_id')->nullable()->constrained('release_readiness_runs')->nullOnDelete();
                $table->foreignId('release_decision_snapshot_id')->nullable()->constrained('release_decision_snapshots')->nullOnDelete();
                $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('finalized_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('title');
                $table->string('release_version')->nullable();
                $table->string('target_environment')->nullable();
                $table->string('gate_profile', 40)->default('standard');
                $table->string('status', 40)->default('needs_review');
                $table->string('automated_decision', 40)->default('needs_review');
                $table->string('final_decision', 40)->default('pending');
                $table->unsignedTinyInteger('score')->default(0);
                $table->string('grade', 10)->default('D');
                $table->unsignedInteger('blocker_count')->default(0);
                $table->unsignedInteger('warning_count')->default(0);
                $table->unsignedInteger('passed_item_count')->default(0);
                $table->unsignedInteger('total_item_count')->default(0);
                $table->unsignedInteger('evidence_count')->default(0);
                $table->unsignedInteger('verified_evidence_count')->default(0);
                $table->unsignedInteger('test_run_count')->default(0);
                $table->unsignedInteger('failed_test_run_count')->default(0);
                $table->unsignedInteger('open_finding_count')->default(0);
                $table->unsignedInteger('high_critical_open_count')->default(0);
                $table->json('summary_json')->nullable();
                $table->json('source_state_json')->nullable();
                $table->text('decision_note')->nullable();
                $table->timestamp('evaluated_at')->nullable();
                $table->timestamp('finalized_at')->nullable();
                $table->timestamps();

                $table->index(['project_id', 'status']);
                $table->index(['project_id', 'final_decision']);
                $table->index(['release_readiness_run_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('release_gates');
    }
};
