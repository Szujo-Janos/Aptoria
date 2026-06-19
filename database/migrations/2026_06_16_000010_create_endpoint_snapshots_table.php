<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('endpoint_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('endpoint_test_batch_id')->nullable()->constrained('endpoint_test_batches')->nullOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('status', 32)->default('captured');
            $table->string('tone', 32)->default('secondary');
            $table->unsignedInteger('total')->default(0);
            $table->unsignedInteger('passed')->default(0);
            $table->unsignedInteger('warning')->default(0);
            $table->unsignedInteger('failed')->default(0);
            $table->unsignedInteger('skipped')->default(0);
            $table->string('checksum', 64)->nullable();
            $table->json('summary_json')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('captured_at')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'captured_at']);
            $table->index(['project_id', 'status']);
            $table->index(['endpoint_test_batch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('endpoint_snapshots');
    }
};
