<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('api_monitors', function (Blueprint $table): void {
            $table->string('alert_email', 180)->nullable()->after('notify_dashboard');
            $table->text('alert_webhook_url')->nullable()->after('alert_email');
            $table->boolean('alert_on_recovery')->default(true)->after('alert_webhook_url');
            $table->timestamp('last_alert_at')->nullable()->after('last_message');
            $table->string('last_alert_status', 40)->nullable()->after('last_alert_at');
        });
    }

    public function down(): void
    {
        Schema::table('api_monitors', function (Blueprint $table): void {
            $table->dropColumn([
                'alert_email',
                'alert_webhook_url',
                'alert_on_recovery',
                'last_alert_at',
                'last_alert_status',
            ]);
        });
    }
};
