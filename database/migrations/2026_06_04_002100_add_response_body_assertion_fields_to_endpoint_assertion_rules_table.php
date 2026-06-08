<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('endpoint_assertion_rules', function (Blueprint $table): void {
            if (! Schema::hasColumn('endpoint_assertion_rules', 'target_path')) {
                $table->string('target_path', 500)->nullable()->after('operator');
            }
        });
    }

    public function down(): void
    {
        Schema::table('endpoint_assertion_rules', function (Blueprint $table): void {
            if (Schema::hasColumn('endpoint_assertion_rules', 'target_path')) {
                $table->dropColumn('target_path');
            }
        });
    }
};
