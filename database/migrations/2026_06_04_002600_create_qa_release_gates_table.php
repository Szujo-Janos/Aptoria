<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qa_release_gates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('release_name', 180);
            $table->string('target_environment', 120)->nullable();
            $table->string('gate_profile', 40)->default('standard');
            $table->string('automated_status', 30)->default('blocked');
            $table->string('final_decision', 40)->default('pending');
            $table->unsignedTinyInteger('score')->default(0);
            $table->string('grade', 8)->nullable();
            $table->unsignedInteger('endpoint_count')->default(0);
            $table->unsignedTinyInteger('endpoint_coverage_percent')->default(0);
            $table->unsignedTinyInteger('qa_coverage_percent')->default(0);
            $table->unsignedTinyInteger('test_execution_percent')->default(0);
            $table->unsignedTinyInteger('test_pass_rate')->default(0);
            $table->unsignedInteger('blocker_count')->default(0);
            $table->unsignedInteger('warning_count')->default(0);
            $table->unsignedInteger('evidence_count')->default(0);
            $table->string('reviewed_by', 160)->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('decision_notes')->nullable();
            $table->json('summary_json')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'created_at']);
            $table->index(['project_id', 'automated_status']);
            $table->index(['project_id', 'final_decision']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qa_release_gates');
    }
};
