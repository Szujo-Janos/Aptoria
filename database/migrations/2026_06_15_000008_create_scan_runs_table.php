<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('scan_runs')) {
            Schema::create('scan_runs', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('project_id')->constrained()->cascadeOnDelete();
                $table->foreignId('environment_id')->nullable()->constrained('environments')->nullOnDelete();
                $table->foreignId('auth_profile_id')->nullable()->constrained('auth_profiles')->nullOnDelete();
                $table->string('profile', 40)->default('safe');
                $table->string('status', 40)->default('queued')->index();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->unsignedInteger('duration_ms')->nullable();
                $table->json('summary_json')->nullable();
                $table->text('error_message')->nullable();
                $table->timestamps();

                $table->index(['project_id', 'created_at']);
                $table->index(['project_id', 'environment_id']);
            });

            return;
        }

        Schema::table('scan_runs', function (Blueprint $table): void {
            if (! Schema::hasColumn('scan_runs', 'project_id')) {
                $table->foreignId('project_id')->nullable()->constrained()->cascadeOnDelete();
            }
            if (! Schema::hasColumn('scan_runs', 'environment_id')) {
                $table->foreignId('environment_id')->nullable()->constrained('environments')->nullOnDelete();
            }
            if (! Schema::hasColumn('scan_runs', 'auth_profile_id')) {
                $table->foreignId('auth_profile_id')->nullable()->constrained('auth_profiles')->nullOnDelete();
            }
            if (! Schema::hasColumn('scan_runs', 'profile')) {
                $table->string('profile', 40)->default('safe');
            }
            if (! Schema::hasColumn('scan_runs', 'status')) {
                $table->string('status', 40)->default('queued');
            }
            if (! Schema::hasColumn('scan_runs', 'started_at')) {
                $table->timestamp('started_at')->nullable();
            }
            if (! Schema::hasColumn('scan_runs', 'completed_at')) {
                $table->timestamp('completed_at')->nullable();
            }
            if (! Schema::hasColumn('scan_runs', 'duration_ms')) {
                $table->unsignedInteger('duration_ms')->nullable();
            }
            if (! Schema::hasColumn('scan_runs', 'summary_json')) {
                $table->json('summary_json')->nullable();
            }
            if (! Schema::hasColumn('scan_runs', 'error_message')) {
                $table->text('error_message')->nullable();
            }
            if (! Schema::hasColumn('scan_runs', 'created_at')) {
                $table->timestamps();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scan_runs');
    }
};
