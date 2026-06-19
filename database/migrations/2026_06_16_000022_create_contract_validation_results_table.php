<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('contract_validation_results')) {
            return;
        }

        Schema::create('contract_validation_results', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contract_validation_run_id')->constrained('contract_validation_runs')->cascadeOnDelete();
            $table->foreignId('endpoint_id')->nullable()->constrained('endpoints')->nullOnDelete();
            $table->string('result_type');
            $table->string('severity')->default('info');
            $table->string('method', 12);
            $table->string('path', 1000);
            $table->string('operation_id')->nullable();
            $table->string('summary');
            $table->json('details_json')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'result_type']);
            $table->index(['contract_validation_run_id', 'severity']);
            $table->index(['method', 'path']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_validation_results');
    }
};
