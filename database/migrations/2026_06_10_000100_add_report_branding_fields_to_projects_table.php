<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            $table->string('report_client_name', 160)->nullable()->after('base_url');
            $table->string('report_organization', 160)->nullable()->after('report_client_name');
            $table->string('report_prepared_by', 120)->nullable()->after('report_organization');
            $table->string('report_role_title', 160)->nullable()->after('report_prepared_by');
            $table->string('report_confidentiality_label', 120)->nullable()->after('report_role_title');
            $table->text('report_disclaimer')->nullable()->after('report_confidentiality_label');
            $table->string('report_logo_path', 500)->nullable()->after('report_disclaimer');
            $table->string('report_logo_original_name', 255)->nullable()->after('report_logo_path');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            $table->dropColumn([
                'report_client_name',
                'report_organization',
                'report_prepared_by',
                'report_role_title',
                'report_confidentiality_label',
                'report_disclaimer',
                'report_logo_path',
                'report_logo_original_name',
            ]);
        });
    }
};
