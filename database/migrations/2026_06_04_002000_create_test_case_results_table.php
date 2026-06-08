<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_case_results', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('test_case_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('scan_run_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('scan_result_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 30);
            $table->text('actual_result')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'status']);
            $table->index(['test_case_id', 'executed_at']);
            $table->index(['scan_result_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_case_results');
    }
};
