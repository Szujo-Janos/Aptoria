<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('findings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('endpoint_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('test_case_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('scan_run_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('scan_result_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('contract_validation_result_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title', 220);
            $table->text('description')->nullable();
            $table->string('source', 40)->default('manual');
            $table->string('severity', 30)->default('medium');
            $table->string('status', 40)->default('open');
            $table->text('reproduction_steps')->nullable();
            $table->text('expected_result')->nullable();
            $table->text('actual_result')->nullable();
            $table->text('recommendation')->nullable();
            $table->timestamp('detected_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'status']);
            $table->index(['project_id', 'severity']);
            $table->index(['project_id', 'source']);
            $table->index(['endpoint_id', 'status']);
            $table->index(['test_case_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('findings');
    }
};
