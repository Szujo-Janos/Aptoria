<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_cases', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('test_suite_id')->constrained()->cascadeOnDelete();
            $table->foreignId('endpoint_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title', 220);
            $table->text('description')->nullable();
            $table->text('preconditions')->nullable();
            $table->text('steps');
            $table->text('expected_result');
            $table->text('actual_result')->nullable();
            $table->string('type', 30)->default('manual');
            $table->string('priority', 30)->default('medium');
            $table->string('status', 30)->default('draft');
            $table->string('last_run_status', 30)->nullable();
            $table->timestamp('last_run_at')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'test_suite_id']);
            $table->index(['project_id', 'endpoint_id']);
            $table->index(['project_id', 'status']);
            $table->index(['project_id', 'last_run_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_cases');
    }
};
