<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('report_versions')) {
            return;
        }

        Schema::table('report_versions', function (Blueprint $table): void {
            if (! Schema::hasColumn('report_versions', 'release_gate_id')) {
                $table->unsignedBigInteger('release_gate_id')->nullable()->after('release_decision_snapshot_id')->index();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('report_versions') || ! Schema::hasColumn('report_versions', 'release_gate_id')) {
            return;
        }

        Schema::table('report_versions', function (Blueprint $table): void {
            $table->dropColumn('release_gate_id');
        });
    }
};
