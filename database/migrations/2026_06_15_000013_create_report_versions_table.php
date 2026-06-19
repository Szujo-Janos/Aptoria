<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('report_versions')) {
            return;
        }

        Schema::create('report_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('generated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('release_readiness_run_id')->nullable()->constrained('release_readiness_runs')->nullOnDelete();
            $table->string('type')->default('full_project');
            $table->string('status')->default('draft');
            $table->string('title');
            $table->longText('content_markdown')->nullable();
            $table->longText('content_html')->nullable();
            $table->json('data_json')->nullable();
            $table->string('checksum', 64)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'type']);
            $table->index(['project_id', 'generated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_versions');
    }
};
