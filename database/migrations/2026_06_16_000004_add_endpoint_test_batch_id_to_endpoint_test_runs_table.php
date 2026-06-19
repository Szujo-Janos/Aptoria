<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('endpoint_test_runs', 'endpoint_test_batch_id')) {
            Schema::table('endpoint_test_runs', function (Blueprint $table) {
                $table->unsignedBigInteger('endpoint_test_batch_id')->nullable()->after('project_id');
                $table->index(['endpoint_test_batch_id', 'checked_at'], 'endpoint_test_runs_batch_checked_index');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('endpoint_test_runs', 'endpoint_test_batch_id')) {
            Schema::table('endpoint_test_runs', function (Blueprint $table) {
                $table->dropIndex('endpoint_test_runs_batch_checked_index');
                $table->dropColumn('endpoint_test_batch_id');
            });
        }
    }
};
