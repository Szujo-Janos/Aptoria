<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('endpoints', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('environment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('auth_profile_id')->nullable()->constrained()->nullOnDelete();
            $table->string('method', 12);
            $table->string('path', 500);
            $table->string('name', 150)->nullable();
            $table->text('description')->nullable();
            $table->string('tags', 500)->nullable();
            $table->boolean('auth_required')->default(false);
            $table->unsignedSmallInteger('expected_status')->nullable();
            $table->string('expected_content_type', 120)->nullable();
            $table->string('risk_level', 30)->default('review');
            $table->text('risk_reason')->nullable();
            $table->text('qa_notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('excluded_from_scan')->default(false);
            $table->timestamps();

            $table->unique(['project_id', 'method', 'path']);
            $table->index(['project_id', 'risk_level']);
            $table->index(['project_id', 'is_active']);
            $table->index(['project_id', 'excluded_from_scan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('endpoints');
    }
};
