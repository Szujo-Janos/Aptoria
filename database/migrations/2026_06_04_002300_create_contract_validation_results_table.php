<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_validation_results', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('contract_validation_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('endpoint_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('scan_result_id')->nullable()->constrained()->nullOnDelete();
            $table->string('method', 12)->nullable();
            $table->string('path', 500)->nullable();
            $table->string('check_type', 80);
            $table->string('severity', 30)->default('medium');
            $table->string('status', 30);
            $table->text('message');
            $table->text('expected')->nullable();
            $table->text('actual')->nullable();
            $table->json('evidence_json')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'status']);
            $table->index(['project_id', 'severity']);
            $table->index(['endpoint_id', 'created_at']);
            $table->index(['contract_validation_run_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_validation_results');
    }
};
