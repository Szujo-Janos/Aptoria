<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'report_display_name')) {
                $table->string('report_display_name', 120)->nullable()->after('timezone');
            }

            if (! Schema::hasColumn('users', 'report_role_title')) {
                $table->string('report_role_title', 160)->nullable()->after('report_display_name');
            }

            if (! Schema::hasColumn('users', 'report_organization')) {
                $table->string('report_organization', 160)->nullable()->after('report_role_title');
            }

            if (! Schema::hasColumn('users', 'report_github_url')) {
                $table->string('report_github_url', 255)->nullable()->after('report_organization');
            }

            if (! Schema::hasColumn('users', 'report_website_url')) {
                $table->string('report_website_url', 255)->nullable()->after('report_github_url');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            foreach (['report_website_url', 'report_github_url', 'report_organization', 'report_role_title', 'report_display_name'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
