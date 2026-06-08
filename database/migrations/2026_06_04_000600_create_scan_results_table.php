<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scan_results', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('scan_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('endpoint_id')->nullable()->constrained()->nullOnDelete();
            $table->string('method', 12);
            $table->string('url', 1000);
            $table->string('status', 30)->default('pending');
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->string('content_type', 160)->nullable();
            $table->unsignedInteger('response_size')->nullable();
            $table->json('headers_json')->nullable();
            $table->text('body_preview')->nullable();
            $table->text('error_message')->nullable();
            $table->string('risk_level', 30)->nullable();
            $table->text('risk_reason')->nullable();
            $table->boolean('expected_status_matched')->nullable();
            $table->boolean('expected_content_type_matched')->nullable();
            $table->timestamps();

            $table->index(['scan_run_id', 'status']);
            $table->index(['endpoint_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scan_results');
    }
};
