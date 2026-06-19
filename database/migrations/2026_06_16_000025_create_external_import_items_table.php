<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('external_import_items')) {
            return;
        }

        Schema::create('external_import_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('external_import_run_id')->constrained('external_import_runs')->cascadeOnDelete();
            $table->foreignId('endpoint_id')->nullable()->constrained('endpoints')->nullOnDelete();
            $table->foreignId('finding_id')->nullable()->constrained('findings')->nullOnDelete();
            $table->string('entity_type', 40);
            $table->string('action', 40)->default('create');
            $table->string('severity', 40)->default('info');
            $table->string('external_key')->nullable();
            $table->string('method', 12)->nullable();
            $table->string('path', 1000)->nullable();
            $table->string('title');
            $table->text('summary')->nullable();
            $table->json('payload_json')->nullable();
            $table->string('status', 40)->default('previewed');
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'entity_type']);
            $table->index(['external_import_run_id', 'status']);
            $table->index(['method', 'path']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_import_items');
    }
};
