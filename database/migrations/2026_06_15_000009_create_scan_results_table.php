<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('scan_results')) {
            Schema::create('scan_results', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('scan_run_id')->constrained('scan_runs')->cascadeOnDelete();
                $table->foreignId('project_id')->constrained()->cascadeOnDelete();
                $table->foreignId('endpoint_id')->nullable()->constrained('endpoints')->nullOnDelete();
                $table->foreignId('environment_id')->nullable()->constrained('environments')->nullOnDelete();
                $table->foreignId('auth_profile_id')->nullable()->constrained('auth_profiles')->nullOnDelete();
                $table->string('method', 12)->default('GET');
                $table->string('url', 1200);
                $table->string('status', 40)->default('skipped')->index();
                $table->unsignedSmallInteger('status_code')->nullable();
                $table->unsignedInteger('response_time_ms')->nullable();
                $table->string('content_type', 180)->nullable();
                $table->unsignedInteger('response_size')->nullable();
                $table->json('headers_json')->nullable();
                $table->text('body_preview')->nullable();
                $table->text('error_message')->nullable();
                $table->boolean('expected_status_matched')->nullable();
                $table->boolean('expected_content_type_matched')->nullable();
                $table->string('risk_level', 40)->default('low');
                $table->text('risk_reason')->nullable();
                $table->timestamps();

                $table->index(['project_id', 'status']);
                $table->index(['scan_run_id', 'status']);
                $table->index(['endpoint_id', 'created_at']);
            });

            return;
        }

        Schema::table('scan_results', function (Blueprint $table): void {
            foreach (['scan_run_id', 'project_id', 'endpoint_id', 'environment_id', 'auth_profile_id'] as $column) {
                if (! Schema::hasColumn('scan_results', $column)) {
                    $column === 'scan_run_id'
                        ? $table->foreignId($column)->nullable()->constrained('scan_runs')->cascadeOnDelete()
                        : $table->foreignId($column)->nullable();
                }
            }
            if (! Schema::hasColumn('scan_results', 'method')) { $table->string('method', 12)->default('GET'); }
            if (! Schema::hasColumn('scan_results', 'url')) { $table->string('url', 1200)->default(''); }
            if (! Schema::hasColumn('scan_results', 'status')) { $table->string('status', 40)->default('skipped'); }
            if (! Schema::hasColumn('scan_results', 'status_code')) { $table->unsignedSmallInteger('status_code')->nullable(); }
            if (! Schema::hasColumn('scan_results', 'response_time_ms')) { $table->unsignedInteger('response_time_ms')->nullable(); }
            if (! Schema::hasColumn('scan_results', 'content_type')) { $table->string('content_type', 180)->nullable(); }
            if (! Schema::hasColumn('scan_results', 'response_size')) { $table->unsignedInteger('response_size')->nullable(); }
            if (! Schema::hasColumn('scan_results', 'headers_json')) { $table->json('headers_json')->nullable(); }
            if (! Schema::hasColumn('scan_results', 'body_preview')) { $table->text('body_preview')->nullable(); }
            if (! Schema::hasColumn('scan_results', 'error_message')) { $table->text('error_message')->nullable(); }
            if (! Schema::hasColumn('scan_results', 'expected_status_matched')) { $table->boolean('expected_status_matched')->nullable(); }
            if (! Schema::hasColumn('scan_results', 'expected_content_type_matched')) { $table->boolean('expected_content_type_matched')->nullable(); }
            if (! Schema::hasColumn('scan_results', 'risk_level')) { $table->string('risk_level', 40)->default('low'); }
            if (! Schema::hasColumn('scan_results', 'risk_reason')) { $table->text('risk_reason')->nullable(); }
            if (! Schema::hasColumn('scan_results', 'created_at')) { $table->timestamps(); }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scan_results');
    }
};
