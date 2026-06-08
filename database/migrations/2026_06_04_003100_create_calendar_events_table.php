<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->foreignId('endpoint_id')->nullable()->constrained('endpoints')->nullOnDelete();
            $table->foreignId('api_monitor_id')->nullable()->constrained('api_monitors')->nullOnDelete();
            $table->foreignId('monitor_alert_event_id')->nullable()->constrained('monitor_alert_events')->nullOnDelete();
            $table->foreignId('qa_release_gate_id')->nullable()->constrained('qa_release_gates')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 220);
            $table->text('description')->nullable();
            $table->string('event_type', 60)->default('manual_qa_task');
            $table->string('status', 40)->default('planned');
            $table->string('priority', 40)->default('normal');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->boolean('all_day')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['starts_at', 'status']);
            $table->index(['project_id', 'starts_at']);
            $table->index(['event_type', 'starts_at']);
            $table->index(['api_monitor_id', 'starts_at']);
            $table->index(['monitor_alert_event_id', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_events');
    }
};
