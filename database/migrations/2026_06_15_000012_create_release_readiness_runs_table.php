<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('release_readiness_runs')) {
            Schema::create('release_readiness_runs', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('project_id')->constrained()->cascadeOnDelete();
                $table->foreignId('generated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('status', 40)->default('blocked')->index();
                $table->unsignedTinyInteger('score')->default(0);
                $table->string('grade', 8)->default('D');
                $table->unsignedSmallInteger('blocker_count')->default(0);
                $table->unsignedSmallInteger('warning_count')->default(0);
                $table->unsignedSmallInteger('check_count')->default(0);
                $table->unsignedSmallInteger('passed_check_count')->default(0);
                $table->json('metrics_json')->nullable();
                $table->json('checks_json')->nullable();
                $table->json('summary_json')->nullable();
                $table->text('decision_note')->nullable();
                $table->timestamp('generated_at')->nullable();
                $table->timestamps();

                $table->index(['project_id', 'created_at']);
                $table->index(['project_id', 'status']);
            });

            return;
        }

        Schema::table('release_readiness_runs', function (Blueprint $table): void {
            if (! Schema::hasColumn('release_readiness_runs', 'project_id')) { $table->foreignId('project_id')->nullable()->constrained()->cascadeOnDelete(); }
            if (! Schema::hasColumn('release_readiness_runs', 'generated_by_user_id')) { $table->foreignId('generated_by_user_id')->nullable()->constrained('users')->nullOnDelete(); }
            if (! Schema::hasColumn('release_readiness_runs', 'status')) { $table->string('status', 40)->default('blocked'); }
            if (! Schema::hasColumn('release_readiness_runs', 'score')) { $table->unsignedTinyInteger('score')->default(0); }
            if (! Schema::hasColumn('release_readiness_runs', 'grade')) { $table->string('grade', 8)->default('D'); }
            if (! Schema::hasColumn('release_readiness_runs', 'blocker_count')) { $table->unsignedSmallInteger('blocker_count')->default(0); }
            if (! Schema::hasColumn('release_readiness_runs', 'warning_count')) { $table->unsignedSmallInteger('warning_count')->default(0); }
            if (! Schema::hasColumn('release_readiness_runs', 'check_count')) { $table->unsignedSmallInteger('check_count')->default(0); }
            if (! Schema::hasColumn('release_readiness_runs', 'passed_check_count')) { $table->unsignedSmallInteger('passed_check_count')->default(0); }
            if (! Schema::hasColumn('release_readiness_runs', 'metrics_json')) { $table->json('metrics_json')->nullable(); }
            if (! Schema::hasColumn('release_readiness_runs', 'checks_json')) { $table->json('checks_json')->nullable(); }
            if (! Schema::hasColumn('release_readiness_runs', 'summary_json')) { $table->json('summary_json')->nullable(); }
            if (! Schema::hasColumn('release_readiness_runs', 'decision_note')) { $table->text('decision_note')->nullable(); }
            if (! Schema::hasColumn('release_readiness_runs', 'generated_at')) { $table->timestamp('generated_at')->nullable(); }
            if (! Schema::hasColumn('release_readiness_runs', 'created_at')) { $table->timestamps(); }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('release_readiness_runs');
    }
};
