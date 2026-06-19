<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('release_readiness_runs')) {
            return;
        }

        Schema::table('release_readiness_runs', function (Blueprint $table): void {
            if (! Schema::hasColumn('release_readiness_runs', 'risk_acceptance_json')) {
                $table->json('risk_acceptance_json')->nullable()->after(
                    Schema::hasColumn('release_readiness_runs', 'retest_closure_json') ? 'retest_closure_json' : 'summary_json'
                );
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('release_readiness_runs')) {
            return;
        }

        Schema::table('release_readiness_runs', function (Blueprint $table): void {
            if (Schema::hasColumn('release_readiness_runs', 'risk_acceptance_json')) {
                $table->dropColumn('risk_acceptance_json');
            }
        });
    }
};
