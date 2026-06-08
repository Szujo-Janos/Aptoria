<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_monitors', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('environment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('baseline_snapshot_id')->nullable()->constrained('snapshots')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name', 180);
            $table->string('frequency', 30)->default('daily');
            $table->boolean('is_enabled')->default(true);
            $table->boolean('auto_snapshot')->default(true);
            $table->boolean('auto_compare')->default(true);
            $table->boolean('notify_dashboard')->default(true);
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->foreignId('last_scan_run_id')->nullable()->constrained('scan_runs')->nullOnDelete();
            $table->foreignId('last_snapshot_id')->nullable()->constrained('snapshots')->nullOnDelete();
            $table->foreignId('last_compare_run_id')->nullable()->constrained('compare_runs')->nullOnDelete();
            $table->string('last_status', 40)->nullable();
            $table->text('last_message')->nullable();
            $table->json('summary_json')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'is_enabled']);
            $table->index(['is_enabled', 'next_run_at']);
            $table->index(['project_id', 'last_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_monitors');
    }
};
