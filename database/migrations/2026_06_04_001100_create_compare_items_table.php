<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compare_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('compare_run_id')->constrained()->cascadeOnDelete();
            $table->string('change_type', 40);
            $table->string('method', 12);
            $table->string('path', 500);
            $table->string('field_changed', 80)->nullable();
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->string('severity', 30)->default('review');
            $table->timestamps();

            $table->index(['compare_run_id', 'change_type']);
            $table->index(['compare_run_id', 'severity']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compare_items');
    }
};
