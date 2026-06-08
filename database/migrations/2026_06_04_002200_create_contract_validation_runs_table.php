<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_validation_runs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('scan_run_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source_name', 180)->nullable();
            $table->string('contract_hash', 64)->nullable();
            $table->string('status', 30)->default('completed');
            $table->unsignedInteger('total_checks')->default(0);
            $table->unsignedInteger('passed_count')->default(0);
            $table->unsignedInteger('warning_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->unsignedInteger('breaking_count')->default(0);
            $table->unsignedInteger('missing_endpoint_count')->default(0);
            $table->unsignedInteger('undocumented_endpoint_count')->default(0);
            $table->unsignedInteger('schema_checked_count')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->text('error_message')->nullable();
            $table->json('summary_json')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'created_at']);
            $table->index(['scan_run_id', 'created_at']);
            $table->index(['project_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_validation_runs');
    }
};
