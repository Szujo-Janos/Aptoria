<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event_type', 80)->default('model');
            $table->string('action', 80);
            $table->string('severity', 30)->default('info');
            $table->string('auditable_type')->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->string('subject_label')->nullable();
            $table->string('subject_name')->nullable();
            $table->string('summary', 500)->nullable();
            $table->string('route_name')->nullable();
            $table->string('http_method', 16)->nullable();
            $table->text('url')->nullable();
            $table->string('ip_address', 64)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('before_values')->nullable();
            $table->json('after_values')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at')->useCurrent();
            $table->timestamps();

            $table->index(['project_id', 'occurred_at']);
            $table->index(['user_id', 'occurred_at']);
            $table->index(['event_type', 'action']);
            $table->index(['auditable_type', 'auditable_id']);
            $table->index('severity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
