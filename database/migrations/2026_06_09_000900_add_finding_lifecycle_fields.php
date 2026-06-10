<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('findings', function (Blueprint $table): void {
            if (! Schema::hasColumn('findings', 'lifecycle_note')) {
                $table->text('lifecycle_note')->nullable()->after('recommendation');
            }
            if (! Schema::hasColumn('findings', 'lifecycle_changed_at')) {
                $table->timestamp('lifecycle_changed_at')->nullable()->after('resolved_at');
            }
            if (! Schema::hasColumn('findings', 'lifecycle_changed_by_user_id')) {
                $table->foreignId('lifecycle_changed_by_user_id')->nullable()->after('lifecycle_changed_at')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('findings', 'reopened_count')) {
                $table->unsignedInteger('reopened_count')->default(0)->after('lifecycle_changed_by_user_id');
            }
        });

        Schema::create('finding_lifecycle_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('finding_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('from_status', 40)->nullable();
            $table->string('to_status', 40);
            $table->text('note')->nullable();
            $table->timestamp('changed_at')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'to_status']);
            $table->index(['finding_id', 'changed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finding_lifecycle_events');

        Schema::table('findings', function (Blueprint $table): void {
            if (Schema::hasColumn('findings', 'lifecycle_changed_by_user_id')) {
                $table->dropConstrainedForeignId('lifecycle_changed_by_user_id');
            }
            foreach (['reopened_count', 'lifecycle_changed_at', 'lifecycle_note'] as $column) {
                if (Schema::hasColumn('findings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
