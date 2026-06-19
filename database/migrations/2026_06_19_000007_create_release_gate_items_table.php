<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('release_gate_items')) {
            Schema::create('release_gate_items', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('project_id')->constrained()->cascadeOnDelete();
                $table->foreignId('release_gate_id')->constrained('release_gates')->cascadeOnDelete();
                $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('item_key');
                $table->string('category')->default('readiness');
                $table->string('label');
                $table->string('icon', 80)->default('workflow');
                $table->string('automated_state', 40)->default('warning');
                $table->string('manual_state', 40)->nullable();
                $table->string('effective_state', 40)->default('warning');
                $table->string('severity', 40)->default('warning');
                $table->string('source_type')->nullable();
                $table->unsignedBigInteger('source_id')->nullable();
                $table->unsignedInteger('evidence_count')->default(0);
                $table->text('required_action')->nullable();
                $table->text('reviewer_note')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->json('metadata_json')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamps();

                $table->unique(['release_gate_id', 'item_key']);
                $table->index(['project_id', 'effective_state']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('release_gate_items');
    }
};
