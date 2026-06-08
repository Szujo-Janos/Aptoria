<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('monitor_alert_events', function (Blueprint $table): void {
            $table->timestamp('acknowledged_at')->nullable()->after('delivered_at');
            $table->foreignId('acknowledged_by')->nullable()->after('acknowledged_at')->constrained('users')->nullOnDelete();
            $table->text('acknowledgement_note')->nullable()->after('acknowledged_by');
        });
    }

    public function down(): void
    {
        Schema::table('monitor_alert_events', function (Blueprint $table): void {
            $table->dropForeign(['acknowledged_by']);
            $table->dropColumn(['acknowledged_at', 'acknowledged_by', 'acknowledgement_note']);
        });
    }
};
