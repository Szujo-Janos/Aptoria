<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('snapshot_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('snapshot_id')->constrained()->cascadeOnDelete();
            $table->foreignId('endpoint_id')->nullable()->constrained()->nullOnDelete();
            $table->string('method', 12);
            $table->string('path', 500);
            $table->boolean('auth_required')->default(false);
            $table->string('risk_level', 30)->nullable();
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->string('content_type', 160)->nullable();
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->unsignedSmallInteger('expected_status')->nullable();
            $table->string('expected_content_type', 120)->nullable();
            $table->string('source_hash', 64)->nullable();
            $table->json('metadata_json')->nullable();
            $table->timestamps();

            $table->unique(['snapshot_id', 'method', 'path']);
            $table->index(['snapshot_id', 'risk_level']);
            $table->index(['method', 'path']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('snapshot_items');
    }
};
