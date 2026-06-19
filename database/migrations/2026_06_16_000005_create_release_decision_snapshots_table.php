<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('release_decision_snapshots')) {
            Schema::create('release_decision_snapshots', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('project_id')->constrained()->cascadeOnDelete();
                $table->foreignId('release_readiness_run_id')->nullable()->constrained('release_readiness_runs')->nullOnDelete();
                $table->foreignId('decided_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('decision', 40)->default('needs_review')->index();
                $table->string('title', 180)->nullable();
                $table->longText('evidence_summary_markdown')->nullable();
                $table->json('evidence_summary_json')->nullable();
                $table->json('readiness_metrics_json')->nullable();
                $table->json('readiness_checks_json')->nullable();
                $table->json('source_state_json')->nullable();
                $table->text('decision_note')->nullable();
                $table->timestamp('decided_at')->nullable();
                $table->timestamps();

                $table->index(['project_id', 'decided_at']);
                $table->index(['project_id', 'decision']);
            });

            return;
        }

        Schema::table('release_decision_snapshots', function (Blueprint $table): void {
            if (! Schema::hasColumn('release_decision_snapshots', 'project_id')) { $table->foreignId('project_id')->nullable()->constrained()->cascadeOnDelete(); }
            if (! Schema::hasColumn('release_decision_snapshots', 'release_readiness_run_id')) { $table->foreignId('release_readiness_run_id')->nullable()->constrained('release_readiness_runs')->nullOnDelete(); }
            if (! Schema::hasColumn('release_decision_snapshots', 'decided_by_user_id')) { $table->foreignId('decided_by_user_id')->nullable()->constrained('users')->nullOnDelete(); }
            if (! Schema::hasColumn('release_decision_snapshots', 'decision')) { $table->string('decision', 40)->default('needs_review'); }
            if (! Schema::hasColumn('release_decision_snapshots', 'title')) { $table->string('title', 180)->nullable(); }
            if (! Schema::hasColumn('release_decision_snapshots', 'evidence_summary_markdown')) { $table->longText('evidence_summary_markdown')->nullable(); }
            if (! Schema::hasColumn('release_decision_snapshots', 'evidence_summary_json')) { $table->json('evidence_summary_json')->nullable(); }
            if (! Schema::hasColumn('release_decision_snapshots', 'readiness_metrics_json')) { $table->json('readiness_metrics_json')->nullable(); }
            if (! Schema::hasColumn('release_decision_snapshots', 'readiness_checks_json')) { $table->json('readiness_checks_json')->nullable(); }
            if (! Schema::hasColumn('release_decision_snapshots', 'source_state_json')) { $table->json('source_state_json')->nullable(); }
            if (! Schema::hasColumn('release_decision_snapshots', 'decision_note')) { $table->text('decision_note')->nullable(); }
            if (! Schema::hasColumn('release_decision_snapshots', 'decided_at')) { $table->timestamp('decided_at')->nullable(); }
            if (! Schema::hasColumn('release_decision_snapshots', 'created_at')) { $table->timestamps(); }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('release_decision_snapshots');
    }
};
