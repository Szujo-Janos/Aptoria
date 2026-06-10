<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('environments', function (Blueprint $table): void {
            $table->string('environment_type', 30)->default('custom')->after('base_url');
        });

        DB::table('environments')
            ->where('is_production', true)
            ->update(['environment_type' => 'production']);

        foreach (['local', 'dev', 'staging'] as $type) {
            DB::table('environments')
                ->whereRaw('LOWER(name) = ?', [$type])
                ->where('is_production', false)
                ->update(['environment_type' => $type]);
        }
    }

    public function down(): void
    {
        Schema::table('environments', function (Blueprint $table): void {
            $table->dropColumn('environment_type');
        });
    }
};
