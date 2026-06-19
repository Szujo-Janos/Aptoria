<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('release_readiness_runs')) {
            Schema::table('release_readiness_runs', function (Blueprint $table): void {
                if (! Schema::hasColumn('release_readiness_runs', 'readiness_profile_key')) {
                    $table->string('readiness_profile_key', 80)->nullable()->after('rules_json');
                }
                if (! Schema::hasColumn('release_readiness_runs', 'rule_deviations_json')) {
                    $table->json('rule_deviations_json')->nullable()->after('readiness_profile_key');
                }
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('release_readiness_runs')) {
            return;
        }

        Schema::table('release_readiness_runs', function (Blueprint $table): void {
            foreach (['rule_deviations_json', 'readiness_profile_key'] as $column) {
                if (Schema::hasColumn('release_readiness_runs', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
