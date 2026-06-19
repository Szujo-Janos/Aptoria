<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('release_gate_events')) {
            Schema::create('release_gate_events', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('project_id')->constrained()->cascadeOnDelete();
                $table->foreignId('release_gate_id')->constrained('release_gates')->cascadeOnDelete();
                $table->foreignId('release_gate_item_id')->nullable()->constrained('release_gate_items')->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('event_type', 80);
                $table->string('summary');
                $table->string('severity', 40)->default('info');
                $table->json('metadata_json')->nullable();
                $table->timestamp('occurred_at')->nullable();
                $table->timestamps();

                $table->index(['project_id', 'event_type']);
                $table->index(['release_gate_id', 'occurred_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('release_gate_events');
    }
};
