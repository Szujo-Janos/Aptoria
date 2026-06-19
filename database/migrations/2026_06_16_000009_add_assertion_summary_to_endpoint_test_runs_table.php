<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('endpoint_test_runs', function (Blueprint $table) {
            $table->unsignedInteger('assertion_total')->default(0)->after('content_type_matched');
            $table->unsignedInteger('assertion_passed')->default(0)->after('assertion_total');
            $table->unsignedInteger('assertion_failed')->default(0)->after('assertion_passed');
            $table->json('assertion_summary_json')->nullable()->after('assertion_failed');
        });
    }

    public function down(): void
    {
        Schema::table('endpoint_test_runs', function (Blueprint $table) {
            $table->dropColumn(['assertion_total', 'assertion_passed', 'assertion_failed', 'assertion_summary_json']);
        });
    }
};
