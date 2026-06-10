<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('test_cases', function (Blueprint $table): void {
            if (! Schema::hasColumn('test_cases', 'execution_order')) {
                $table->unsignedInteger('execution_order')->default(0)->after('status');
            }

            if (! Schema::hasColumn('test_cases', 'builder_metadata_json')) {
                $table->json('builder_metadata_json')->nullable()->after('execution_order');
            }
        });
    }

    public function down(): void
    {
        Schema::table('test_cases', function (Blueprint $table): void {
            if (Schema::hasColumn('test_cases', 'builder_metadata_json')) {
                $table->dropColumn('builder_metadata_json');
            }

            if (Schema::hasColumn('test_cases', 'execution_order')) {
                $table->dropColumn('execution_order');
            }
        });
    }
};
