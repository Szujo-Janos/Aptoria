<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('finding_evidence')) {
            Schema::create('finding_evidence', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('project_id')->constrained()->cascadeOnDelete();
                $table->foreignId('finding_id')->nullable()->constrained('findings')->nullOnDelete();
                $table->foreignId('endpoint_id')->nullable()->constrained('endpoints')->nullOnDelete();
                $table->foreignId('scan_result_id')->nullable()->constrained('scan_results')->nullOnDelete();
                $table->foreignId('captured_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('type', 40)->default('note');
                $table->string('title');
                $table->string('source_label')->nullable();
                $table->text('content')->nullable();
                $table->string('url', 1200)->nullable();
                $table->text('request_excerpt')->nullable();
                $table->text('response_excerpt')->nullable();
                $table->timestamp('captured_at')->nullable();
                $table->string('sha256', 64)->nullable();
                $table->json('metadata_json')->nullable();
                $table->timestamps();

                $table->index(['project_id', 'type']);
                $table->index(['finding_id', 'created_at']);
                $table->index(['endpoint_id', 'created_at']);
            });

            return;
        }

        Schema::table('finding_evidence', function (Blueprint $table): void {
            foreach (['project_id', 'finding_id', 'endpoint_id', 'scan_result_id', 'captured_by_user_id'] as $column) {
                if (! Schema::hasColumn('finding_evidence', $column)) {
                    $table->unsignedBigInteger($column)->nullable();
                }
            }
            if (! Schema::hasColumn('finding_evidence', 'type')) { $table->string('type', 40)->default('note'); }
            if (! Schema::hasColumn('finding_evidence', 'title')) { $table->string('title')->default('Evidence'); }
            if (! Schema::hasColumn('finding_evidence', 'source_label')) { $table->string('source_label')->nullable(); }
            if (! Schema::hasColumn('finding_evidence', 'content')) { $table->text('content')->nullable(); }
            if (! Schema::hasColumn('finding_evidence', 'url')) { $table->string('url', 1200)->nullable(); }
            if (! Schema::hasColumn('finding_evidence', 'request_excerpt')) { $table->text('request_excerpt')->nullable(); }
            if (! Schema::hasColumn('finding_evidence', 'response_excerpt')) { $table->text('response_excerpt')->nullable(); }
            if (! Schema::hasColumn('finding_evidence', 'captured_at')) { $table->timestamp('captured_at')->nullable(); }
            if (! Schema::hasColumn('finding_evidence', 'sha256')) { $table->string('sha256', 64)->nullable(); }
            if (! Schema::hasColumn('finding_evidence', 'metadata_json')) { $table->json('metadata_json')->nullable(); }
            if (! Schema::hasColumn('finding_evidence', 'created_at')) { $table->timestamps(); }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finding_evidence');
    }
};
