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
            if (! Schema::hasColumn('release_readiness_runs', 'contract_validation_json')) {
                $table->json('contract_validation_json')->nullable()->after('risk_acceptance_json');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('release_readiness_runs') || ! Schema::hasColumn('release_readiness_runs', 'contract_validation_json')) {
            return;
        }

        Schema::table('release_readiness_runs', function (Blueprint $table): void {
            $table->dropColumn('contract_validation_json');
        });
    }
};
