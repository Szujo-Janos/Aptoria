<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('endpoint_assertion_rules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('endpoint_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('rule_key', 80);
            $table->string('operator', 40);
            $table->text('expected_value')->nullable();
            $table->string('severity', 20)->default('warning');
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->index(['project_id', 'endpoint_id', 'enabled'], 'assertion_rules_scope_enabled_index');
            $table->index(['project_id', 'rule_key'], 'assertion_rules_project_key_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('endpoint_assertion_rules');
    }
};
