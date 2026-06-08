<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('endpoint_path_parameters', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('endpoint_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('parameter_name', 120);
            $table->string('test_value', 500)->nullable();
            $table->text('description')->nullable();
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->unique(['project_id', 'endpoint_id', 'parameter_name'], 'endpoint_path_params_unique');
            $table->index(['project_id', 'endpoint_id']);
            $table->index(['project_id', 'parameter_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('endpoint_path_parameters');
    }
};
