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
            if (! Schema::hasColumn('report_versions', 'client_delivery_count')) {
                $table->unsignedInteger('client_delivery_count')->default(0)->after('archived_at');
            }
            if (! Schema::hasColumn('report_versions', 'client_download_count')) {
                $table->unsignedInteger('client_download_count')->default(0)->after('client_delivery_count');
            }
            if (! Schema::hasColumn('report_versions', 'client_last_delivered_at')) {
                $table->timestamp('client_last_delivered_at')->nullable()->after('client_download_count');
            }
            if (! Schema::hasColumn('report_versions', 'client_last_downloaded_at')) {
                $table->timestamp('client_last_downloaded_at')->nullable()->after('client_last_delivered_at');
            }
            if (! Schema::hasColumn('report_versions', 'client_delivery_summary_json')) {
                $table->json('client_delivery_summary_json')->nullable()->after('client_last_downloaded_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('report_versions')) {
            return;
        }

        Schema::table('report_versions', function (Blueprint $table): void {
            foreach (['client_delivery_summary_json', 'client_last_downloaded_at', 'client_last_delivered_at', 'client_download_count', 'client_delivery_count'] as $column) {
                if (Schema::hasColumn('report_versions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
