<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('api_monitors', function (Blueprint $table): void {
            $table->foreignId('test_suite_id')
                ->nullable()
                ->after('baseline_snapshot_id')
                ->constrained('test_suites')
                ->nullOnDelete();

            $table->index(['project_id', 'test_suite_id']);
        });
    }

    public function down(): void
    {
        Schema::table('api_monitors', function (Blueprint $table): void {
            $table->dropIndex(['project_id', 'test_suite_id']);
            $table->dropForeign(['test_suite_id']);
            $table->dropColumn('test_suite_id');
        });
    }
};
