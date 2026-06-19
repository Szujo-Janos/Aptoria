<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('endpoint_snapshot_compare_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('endpoint_snapshot_compare_id')->constrained('endpoint_snapshot_compares')->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('baseline_item_id')->nullable()->constrained('endpoint_snapshot_items')->nullOnDelete();
            $table->foreignId('target_item_id')->nullable()->constrained('endpoint_snapshot_items')->nullOnDelete();
            $table->string('endpoint_signature', 700);
            $table->string('method', 12)->nullable();
            $table->text('path')->nullable();
            $table->string('change_type', 32);
            $table->string('tone', 32)->default('secondary');
            $table->string('baseline_state', 32)->nullable();
            $table->string('target_state', 32)->nullable();
            $table->unsignedSmallInteger('baseline_status_code')->nullable();
            $table->unsignedSmallInteger('target_status_code')->nullable();
            $table->string('baseline_checksum', 64)->nullable();
            $table->string('target_checksum', 64)->nullable();
            $table->json('summary_json')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'endpoint_snapshot_compare_id']);
            $table->index(['change_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('endpoint_snapshot_compare_items');
    }
};
