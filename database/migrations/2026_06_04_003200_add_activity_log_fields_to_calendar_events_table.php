<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calendar_events', function (Blueprint $table): void {
            $table->boolean('is_system_locked')->default(false)->after('all_day');
            $table->string('activity_action', 40)->nullable()->after('completed_at');
            $table->string('activity_subject_type', 160)->nullable()->after('activity_action');
            $table->unsignedBigInteger('activity_subject_id')->nullable()->after('activity_subject_type');
            $table->string('activity_route', 255)->nullable()->after('activity_subject_id');
            $table->json('activity_payload')->nullable()->after('activity_route');

            $table->index(['is_system_locked', 'starts_at']);
            $table->index(['activity_action', 'starts_at']);
            $table->index(['activity_subject_type', 'activity_subject_id'], 'calendar_activity_subject_index');
        });
    }

    public function down(): void
    {
        Schema::table('calendar_events', function (Blueprint $table): void {
            $table->dropIndex(['is_system_locked', 'starts_at']);
            $table->dropIndex(['activity_action', 'starts_at']);
            $table->dropIndex('calendar_activity_subject_index');
            $table->dropColumn([
                'is_system_locked',
                'activity_action',
                'activity_subject_type',
                'activity_subject_id',
                'activity_route',
                'activity_payload',
            ]);
        });
    }
};
