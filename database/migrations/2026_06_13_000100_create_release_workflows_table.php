<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('release_workflows', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('release_decision_id')->nullable()->constrained('release_decisions')->nullOnDelete();
            $table->string('overall_state')->default('not_started');
            $table->unsignedTinyInteger('progress_percent')->default(0);
            $table->unsignedInteger('completed_steps')->default(0);
            $table->unsignedInteger('blocked_steps')->default(0);
            $table->unsignedInteger('needs_review_steps')->default(0);
            $table->unsignedInteger('ready_steps')->default(0);
            $table->unsignedInteger('not_started_steps')->default(0);
            $table->unsignedInteger('skipped_steps')->default(0);
            $table->unsignedInteger('blocker_count')->default(0);
            $table->unsignedInteger('missing_evidence_count')->default(0);
            $table->string('next_step_key')->nullable();
            $table->json('snapshot_json')->nullable();
            $table->timestamp('evaluated_at')->nullable();
            $table->timestamps();

            $table->unique('project_id');
            $table->index(['overall_state', 'evaluated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('release_workflows');
    }
};
