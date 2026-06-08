<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitor_alert_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('api_monitor_id')->constrained('api_monitors')->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->string('channel', 40)->default('dashboard');
            $table->string('severity', 40)->default('warning');
            $table->string('status', 40);
            $table->string('previous_status', 40)->nullable();
            $table->text('message')->nullable();
            $table->json('payload_json')->nullable();
            $table->string('delivery_status', 40)->default('recorded');
            $table->text('delivery_message')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->index(['api_monitor_id', 'created_at']);
            $table->index(['project_id', 'created_at']);
            $table->index(['channel', 'delivery_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitor_alert_events');
    }
};
