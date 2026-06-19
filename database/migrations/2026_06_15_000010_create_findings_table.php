<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('findings')) {
            Schema::create('findings', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('project_id')->constrained()->cascadeOnDelete();
                $table->foreignId('endpoint_id')->nullable()->constrained('endpoints')->nullOnDelete();
                $table->foreignId('scan_run_id')->nullable()->constrained('scan_runs')->nullOnDelete();
                $table->foreignId('scan_result_id')->nullable()->constrained('scan_results')->nullOnDelete();
                $table->string('title');
                $table->string('source', 40)->default('manual');
                $table->string('severity', 40)->default('medium');
                $table->string('status', 40)->default('open');
                $table->string('priority', 40)->default('normal');
                $table->string('owner_name')->nullable();
                $table->date('due_date')->nullable();
                $table->text('summary')->nullable();
                $table->text('reproduction_steps')->nullable();
                $table->text('expected_result')->nullable();
                $table->text('actual_result')->nullable();
                $table->text('recommendation')->nullable();
                $table->boolean('evidence_required')->default(true);
                $table->boolean('retest_required')->default(false);
                $table->json('metadata_json')->nullable();
                $table->timestamps();

                $table->index(['project_id', 'status']);
                $table->index(['project_id', 'severity']);
                $table->index(['endpoint_id', 'status']);
            });

            return;
        }

        Schema::table('findings', function (Blueprint $table): void {
            foreach (['project_id', 'endpoint_id', 'scan_run_id', 'scan_result_id'] as $column) {
                if (! Schema::hasColumn('findings', $column)) {
                    $table->unsignedBigInteger($column)->nullable();
                }
            }
            if (! Schema::hasColumn('findings', 'title')) { $table->string('title')->default('Finding'); }
            if (! Schema::hasColumn('findings', 'source')) { $table->string('source', 40)->default('manual'); }
            if (! Schema::hasColumn('findings', 'severity')) { $table->string('severity', 40)->default('medium'); }
            if (! Schema::hasColumn('findings', 'status')) { $table->string('status', 40)->default('open'); }
            if (! Schema::hasColumn('findings', 'priority')) { $table->string('priority', 40)->default('normal'); }
            if (! Schema::hasColumn('findings', 'owner_name')) { $table->string('owner_name')->nullable(); }
            if (! Schema::hasColumn('findings', 'due_date')) { $table->date('due_date')->nullable(); }
            if (! Schema::hasColumn('findings', 'summary')) { $table->text('summary')->nullable(); }
            if (! Schema::hasColumn('findings', 'reproduction_steps')) { $table->text('reproduction_steps')->nullable(); }
            if (! Schema::hasColumn('findings', 'expected_result')) { $table->text('expected_result')->nullable(); }
            if (! Schema::hasColumn('findings', 'actual_result')) { $table->text('actual_result')->nullable(); }
            if (! Schema::hasColumn('findings', 'recommendation')) { $table->text('recommendation')->nullable(); }
            if (! Schema::hasColumn('findings', 'evidence_required')) { $table->boolean('evidence_required')->default(true); }
            if (! Schema::hasColumn('findings', 'retest_required')) { $table->boolean('retest_required')->default(false); }
            if (! Schema::hasColumn('findings', 'metadata_json')) { $table->json('metadata_json')->nullable(); }
            if (! Schema::hasColumn('findings', 'created_at')) { $table->timestamps(); }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('findings');
    }
};
