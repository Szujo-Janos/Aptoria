<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('release_workflow_steps', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('release_workflow_id')->constrained('release_workflows')->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('step_key');
            $table->string('label');
            $table->string('state')->default('not_started');
            $table->string('computed_state')->default('not_started');
            $table->string('manual_state')->nullable();
            $table->text('manual_reason')->nullable();
            $table->unsignedInteger('blocker_count')->default(0);
            $table->unsignedInteger('missing_evidence_count')->default(0);
            $table->text('required_action')->nullable();
            $table->string('suggested_action_label')->nullable();
            $table->string('suggested_action_url', 2048)->nullable();
            $table->json('completion_criteria_json')->nullable();
            $table->json('blocker_reasons_json')->nullable();
            $table->json('evidence_summary_json')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('skipped_at')->nullable();
            $table->timestamp('reopened_at')->nullable();
            $table->timestamps();

            $table->unique(['release_workflow_id', 'step_key']);
            $table->index(['project_id', 'state']);
            $table->index(['step_key', 'state']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('release_workflow_steps');
    }
};
