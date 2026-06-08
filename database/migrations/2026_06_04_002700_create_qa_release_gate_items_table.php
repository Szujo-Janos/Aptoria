<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qa_release_gate_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('qa_release_gate_id')->constrained('qa_release_gates')->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('endpoint_id')->nullable()->constrained()->nullOnDelete();
            $table->string('item_type', 40);
            $table->string('source', 60)->default('release_readiness');
            $table->string('severity', 30)->default('info');
            $table->string('rule_key', 120)->nullable();
            $table->string('title', 240);
            $table->text('message')->nullable();
            $table->text('recommendation')->nullable();
            $table->json('metadata_json')->nullable();
            $table->timestamps();

            $table->index(['qa_release_gate_id', 'item_type']);
            $table->index(['project_id', 'severity']);
            $table->index(['endpoint_id', 'item_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qa_release_gate_items');
    }
};
