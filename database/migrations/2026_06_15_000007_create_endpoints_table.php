<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('endpoints')) {
            Schema::create('endpoints', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('project_id')->constrained()->cascadeOnDelete();
                $table->foreignId('environment_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('auth_profile_id')->nullable()->constrained()->nullOnDelete();
                $table->string('method', 12)->default('GET');
                $table->string('path', 600);
                $table->string('name')->nullable();
                $table->text('description')->nullable();
                $table->string('tags')->nullable();
                $table->boolean('auth_required')->default(false);
                $table->unsignedSmallInteger('expected_status')->nullable();
                $table->string('expected_content_type', 120)->nullable();
                $table->string('risk_level', 40)->default('low');
                $table->boolean('is_active')->default(true);
                $table->boolean('excluded_from_scan')->default(false);
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['project_id', 'method']);
                $table->index(['project_id', 'risk_level']);
                $table->index(['project_id', 'is_active']);
                $table->unique(['project_id', 'method', 'path']);
            });

            return;
        }

        Schema::table('endpoints', function (Blueprint $table): void {
            if (! Schema::hasColumn('endpoints', 'project_id')) {
                $table->foreignId('project_id')->nullable()->constrained()->cascadeOnDelete();
            }
            if (! Schema::hasColumn('endpoints', 'environment_id')) {
                $table->foreignId('environment_id')->nullable()->constrained()->nullOnDelete();
            }
            if (! Schema::hasColumn('endpoints', 'auth_profile_id')) {
                $table->foreignId('auth_profile_id')->nullable()->constrained()->nullOnDelete();
            }
            if (! Schema::hasColumn('endpoints', 'method')) {
                $table->string('method', 12)->default('GET');
            }
            if (! Schema::hasColumn('endpoints', 'path')) {
                $table->string('path', 600)->default('/');
            }
            if (! Schema::hasColumn('endpoints', 'name')) {
                $table->string('name')->nullable();
            }
            if (! Schema::hasColumn('endpoints', 'description')) {
                $table->text('description')->nullable();
            }
            if (! Schema::hasColumn('endpoints', 'tags')) {
                $table->string('tags')->nullable();
            }
            if (! Schema::hasColumn('endpoints', 'auth_required')) {
                $table->boolean('auth_required')->default(false);
            }
            if (! Schema::hasColumn('endpoints', 'expected_status')) {
                $table->unsignedSmallInteger('expected_status')->nullable();
            }
            if (! Schema::hasColumn('endpoints', 'expected_content_type')) {
                $table->string('expected_content_type', 120)->nullable();
            }
            if (! Schema::hasColumn('endpoints', 'risk_level')) {
                $table->string('risk_level', 40)->default('low');
            }
            if (! Schema::hasColumn('endpoints', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }
            if (! Schema::hasColumn('endpoints', 'excluded_from_scan')) {
                $table->boolean('excluded_from_scan')->default(false);
            }
            if (! Schema::hasColumn('endpoints', 'notes')) {
                $table->text('notes')->nullable();
            }
            if (! Schema::hasColumn('endpoints', 'created_at')) {
                $table->timestamps();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('endpoints');
    }
};
