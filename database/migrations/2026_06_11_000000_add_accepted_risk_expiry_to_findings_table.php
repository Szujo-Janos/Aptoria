<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('findings', function (Blueprint $table): void {
            if (! Schema::hasColumn('findings', 'accepted_risk_expires_at')) {
                $table->timestamp('accepted_risk_expires_at')->nullable()->after('resolved_at');
            }
            if (! Schema::hasColumn('findings', 'accepted_risk_note')) {
                $table->text('accepted_risk_note')->nullable()->after('accepted_risk_expires_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('findings', function (Blueprint $table): void {
            if (Schema::hasColumn('findings', 'accepted_risk_note')) {
                $table->dropColumn('accepted_risk_note');
            }
            if (Schema::hasColumn('findings', 'accepted_risk_expires_at')) {
                $table->dropColumn('accepted_risk_expires_at');
            }
        });
    }
};
