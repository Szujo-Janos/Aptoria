<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('endpoint_assertion_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('endpoint_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name', 180);
            $table->string('rule_key', 80);
            $table->string('operator', 40);
            $table->text('expected_value')->nullable();
            $table->string('target_path', 255)->nullable();
            $table->string('severity', 24)->default('warning');
            $table->boolean('enabled')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'enabled']);
            $table->index(['endpoint_id', 'enabled']);
            $table->index(['rule_key', 'operator']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('endpoint_assertion_rules');
    }
};
