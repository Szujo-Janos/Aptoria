<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('test_runs')) {
            Schema::create('test_runs', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('project_id')->constrained()->cascadeOnDelete();
                $table->foreignId('test_suite_id')->constrained('test_suites')->cascadeOnDelete();
                $table->foreignId('test_case_id')->constrained('test_cases')->cascadeOnDelete();
                $table->foreignId('endpoint_id')->nullable()->constrained('endpoints')->nullOnDelete();
                $table->foreignId('executed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('finding_id')->nullable()->constrained('findings')->nullOnDelete();
                $table->foreignId('finding_evidence_id')->nullable()->constrained('finding_evidence')->nullOnDelete();
                $table->string('status', 40);
                $table->timestamp('executed_at')->nullable();
                $table->unsignedInteger('duration_ms')->nullable();
                $table->string('environment_label')->nullable();
                $table->text('actual_result')->nullable();
                $table->text('failure_summary')->nullable();
                $table->text('evidence_summary')->nullable();
                $table->json('metadata_json')->nullable();
                $table->timestamps();

                $table->index(['project_id', 'status']);
                $table->index(['test_case_id', 'executed_at']);
                $table->index(['finding_evidence_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('test_runs');
    }
};
