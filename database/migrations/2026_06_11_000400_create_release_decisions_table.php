<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('release_decisions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('decision_owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('qa_release_gate_id')->nullable()->constrained('qa_release_gates')->nullOnDelete();
            $table->string('release_name')->nullable();
            $table->string('target_environment')->nullable();
            $table->string('decision_status', 40)->default('pending_evidence')->index();
            $table->timestamp('decided_at')->nullable();
            $table->text('decision_notes')->nullable();
            $table->integer('release_score')->default(0);
            $table->string('readiness_status', 40)->nullable();
            $table->integer('blocker_count')->default(0);
            $table->integer('warning_count')->default(0);
            $table->integer('accepted_risk_count')->default(0);
            $table->integer('blind_spot_count')->default(0);
            $table->json('decision_package_json')->nullable();
            $table->string('package_checksum', 80)->nullable();
            $table->timestamps();

            $table->index(['project_id', 'decision_status']);
            $table->index(['project_id', 'decided_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('release_decisions');
    }
};
