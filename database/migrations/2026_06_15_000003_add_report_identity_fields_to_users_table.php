<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'report_organization')) {
                $table->string('report_organization')->nullable();
            }
            if (! Schema::hasColumn('users', 'report_prepared_by')) {
                $table->string('report_prepared_by')->nullable();
            }
            if (! Schema::hasColumn('users', 'report_role_title')) {
                $table->string('report_role_title')->nullable();
            }
            if (! Schema::hasColumn('users', 'report_confidentiality_label')) {
                $table->string('report_confidentiality_label')->nullable();
            }
            if (! Schema::hasColumn('users', 'report_disclaimer')) {
                $table->text('report_disclaimer')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            foreach ([
                'report_organization',
                'report_prepared_by',
                'report_role_title',
                'report_confidentiality_label',
                'report_disclaimer',
            ] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
