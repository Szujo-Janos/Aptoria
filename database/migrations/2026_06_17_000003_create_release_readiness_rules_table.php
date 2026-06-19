<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('release_readiness_rules')) {
            Schema::create('release_readiness_rules', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('project_id')->constrained()->cascadeOnDelete();
                $table->string('rule_key', 120);
                $table->string('category', 80)->default('core');
                $table->string('icon', 80)->default('check-circle');
                $table->boolean('enabled')->default(true);
                $table->string('failure_level', 40)->default('warning');
                $table->string('default_failure_level', 40)->default('warning');
                $table->unsignedInteger('sort_order')->default(0);
                $table->json('metadata_json')->nullable();
                $table->timestamps();

                $table->unique(['project_id', 'rule_key']);
                $table->index(['project_id', 'enabled']);
                $table->index(['project_id', 'category']);
            });
        }

        if (Schema::hasTable('release_readiness_runs') && ! Schema::hasColumn('release_readiness_runs', 'rules_json')) {
            Schema::table('release_readiness_runs', function (Blueprint $table): void {
                $table->json('rules_json')->nullable()->after('checks_json');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('release_readiness_runs') && Schema::hasColumn('release_readiness_runs', 'rules_json')) {
            Schema::table('release_readiness_runs', function (Blueprint $table): void {
                $table->dropColumn('rules_json');
            });
        }
        Schema::dropIfExists('release_readiness_rules');
    }
};
