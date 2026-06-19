<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('external_import_runs')) {
            return;
        }

        Schema::create('external_import_runs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source_type', 80);
            $table->string('source_name')->nullable();
            $table->string('source_version')->nullable();
            $table->string('status', 40)->default('previewed');
            $table->unsignedInteger('item_count')->default(0);
            $table->unsignedInteger('endpoint_count')->default(0);
            $table->unsignedInteger('assertion_count')->default(0);
            $table->unsignedInteger('finding_count')->default(0);
            $table->unsignedInteger('evidence_count')->default(0);
            $table->unsignedInteger('warning_count')->default(0);
            $table->unsignedInteger('blocker_count')->default(0);
            $table->json('summary_json')->nullable();
            $table->longText('raw_excerpt')->nullable();
            $table->timestamp('previewed_at')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'source_type']);
            $table->index(['project_id', 'status']);
            $table->index('previewed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_import_runs');
    }
};
