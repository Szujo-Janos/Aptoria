<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('generated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('report_type', 60)->default('technical')->index();
            $table->string('report_format', 30)->default('markdown');
            $table->string('status', 30)->default('draft')->index();
            $table->string('content_checksum', 80)->index();
            $table->longText('markdown_content')->nullable();
            $table->json('source_scan_ids')->nullable();
            $table->json('source_snapshot_ids')->nullable();
            $table->json('source_compare_ids')->nullable();
            $table->json('source_finding_state')->nullable();
            $table->json('source_release_gate_ids')->nullable();
            $table->json('source_release_decision_ids')->nullable();
            $table->json('source_evidence_ids')->nullable();
            $table->json('source_options_json')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'status']);
            $table->index(['project_id', 'created_at']);
            $table->index(['project_id', 'approved_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_versions');
    }
};
