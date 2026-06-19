<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('evidence_packs')) {
            Schema::create('evidence_packs', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('project_id')->constrained()->cascadeOnDelete();
                $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('release_readiness_run_id')->nullable()->constrained('release_readiness_runs')->nullOnDelete();
                $table->foreignId('report_version_id')->nullable()->constrained('report_versions')->nullOnDelete();
                $table->string('title');
                $table->string('pack_type', 80)->default('release_evidence');
                $table->string('status', 40)->default('generated');
                $table->json('included_sections_json')->nullable();
                $table->json('manifest_json')->nullable();
                $table->longText('content_markdown')->nullable();
                $table->longText('content_html')->nullable();
                $table->string('checksum', 64)->nullable();
                $table->timestamp('generated_at')->nullable();
                $table->timestamps();
                $table->index(['project_id', 'created_at']);
                $table->index(['project_id', 'pack_type']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('evidence_packs');
    }
};
