<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('endpoint_snapshot_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('endpoint_snapshot_id')->constrained('endpoint_snapshots')->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('endpoint_id')->nullable()->constrained()->nullOnDelete();
            $table->string('endpoint_signature', 700);
            $table->string('endpoint_name')->nullable();
            $table->string('method', 12);
            $table->text('path')->nullable();
            $table->text('url')->nullable();
            $table->string('state', 32)->default('skipped');
            $table->string('tone', 32)->default('secondary');
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->string('content_type', 255)->nullable();
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->unsignedInteger('response_size')->nullable();
            $table->unsignedInteger('assertion_total')->default(0);
            $table->unsignedInteger('assertion_failed')->default(0);
            $table->string('item_checksum', 64);
            $table->json('evidence_json')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'endpoint_snapshot_id']);
            $table->index(['endpoint_id']);
            $table->index(['state']);
            $table->index('item_checksum');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('endpoint_snapshot_items');
    }
};
