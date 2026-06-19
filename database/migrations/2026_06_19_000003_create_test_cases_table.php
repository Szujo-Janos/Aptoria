<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('test_cases')) {
            Schema::create('test_cases', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('project_id')->constrained()->cascadeOnDelete();
                $table->foreignId('test_suite_id')->constrained('test_suites')->cascadeOnDelete();
                $table->foreignId('endpoint_id')->nullable()->constrained('endpoints')->nullOnDelete();
                $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('title');
                $table->text('description')->nullable();
                $table->text('preconditions')->nullable();
                $table->text('steps')->nullable();
                $table->text('expected_result')->nullable();
                $table->string('type', 40)->default('manual');
                $table->string('priority', 40)->default('normal');
                $table->string('status', 40)->default('active');
                $table->string('tags')->nullable();
                $table->string('source', 80)->default('native');
                $table->string('external_reference')->nullable();
                $table->string('last_run_status', 40)->nullable();
                $table->timestamp('last_run_at')->nullable();
                $table->unsignedInteger('run_count')->default(0);
                $table->unsignedInteger('pass_count')->default(0);
                $table->unsignedInteger('fail_count')->default(0);
                $table->json('metadata_json')->nullable();
                $table->timestamps();

                $table->index(['project_id', 'status']);
                $table->index(['project_id', 'priority']);
                $table->index(['test_suite_id', 'status']);
                $table->index(['endpoint_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('test_cases');
    }
};
