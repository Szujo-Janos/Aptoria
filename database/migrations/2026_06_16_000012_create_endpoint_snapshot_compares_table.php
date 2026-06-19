<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('endpoint_snapshot_compares', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('baseline_snapshot_id')->constrained('endpoint_snapshots')->cascadeOnDelete();
            $table->foreignId('target_snapshot_id')->constrained('endpoint_snapshots')->cascadeOnDelete();
            $table->foreignId('compared_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 32)->default('passed');
            $table->string('tone', 32)->default('success');
            $table->unsignedInteger('total_items')->default(0);
            $table->unsignedInteger('unchanged_count')->default(0);
            $table->unsignedInteger('changed_count')->default(0);
            $table->unsignedInteger('added_count')->default(0);
            $table->unsignedInteger('removed_count')->default(0);
            $table->unsignedInteger('regressed_count')->default(0);
            $table->unsignedInteger('improved_count')->default(0);
            $table->json('summary_json')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('compared_at')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'compared_at']);
            $table->index(['project_id', 'status']);
            $table->index(['baseline_snapshot_id', 'target_snapshot_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('endpoint_snapshot_compares');
    }
};
