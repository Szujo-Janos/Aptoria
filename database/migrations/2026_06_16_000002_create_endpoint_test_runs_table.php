<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('endpoint_test_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('endpoint_id')->constrained()->cascadeOnDelete();
            $table->foreignId('environment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('auth_profile_id')->nullable()->constrained()->nullOnDelete();
            $table->string('method', 12);
            $table->text('url')->nullable();
            $table->string('state', 32)->default('skipped');
            $table->string('tone', 32)->default('secondary');
            $table->text('message')->nullable();
            $table->unsignedSmallInteger('expected_status')->nullable();
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->boolean('status_matched')->nullable();
            $table->string('expected_content_type', 160)->nullable();
            $table->string('content_type', 255)->nullable();
            $table->boolean('content_type_matched')->nullable();
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->unsignedInteger('response_size')->nullable();
            $table->text('body_preview')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'checked_at']);
            $table->index(['endpoint_id', 'checked_at']);
            $table->index(['state', 'checked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('endpoint_test_runs');
    }
};
