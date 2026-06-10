<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('api_monitors', function (Blueprint $table): void {
            $table->boolean('alert_on_critical_finding')->default(true)->after('alert_on_recovery');
            $table->boolean('alert_on_high_finding')->default(true)->after('alert_on_critical_finding');
            $table->boolean('alert_on_http_5xx')->default(true)->after('alert_on_high_finding');
            $table->boolean('alert_on_sensitive_data')->default(true)->after('alert_on_http_5xx');
            $table->boolean('alert_on_broken_auth')->default(true)->after('alert_on_sensitive_data');
            $table->boolean('alert_on_schema_drift')->default(true)->after('alert_on_broken_auth');
            $table->string('last_alert_fingerprint', 80)->nullable()->after('last_alert_status');
        });
    }

    public function down(): void
    {
        Schema::table('api_monitors', function (Blueprint $table): void {
            $table->dropColumn([
                'alert_on_critical_finding',
                'alert_on_high_finding',
                'alert_on_http_5xx',
                'alert_on_sensitive_data',
                'alert_on_broken_auth',
                'alert_on_schema_drift',
                'last_alert_fingerprint',
            ]);
        });
    }
};
