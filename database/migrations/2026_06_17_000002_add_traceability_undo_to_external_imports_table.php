<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('external_import_runs')) {
            Schema::table('external_import_runs', function (Blueprint $table): void {
                if (! Schema::hasColumn('external_import_runs', 'reverted_by_user_id')) {
                    $table->foreignId('reverted_by_user_id')->nullable()->after('created_by_user_id')->constrained('users')->nullOnDelete();
                }
                if (! Schema::hasColumn('external_import_runs', 'trace_summary_json')) {
                    $table->json('trace_summary_json')->nullable()->after('summary_json');
                }
                if (! Schema::hasColumn('external_import_runs', 'revert_summary_json')) {
                    $table->json('revert_summary_json')->nullable()->after('trace_summary_json');
                }
                if (! Schema::hasColumn('external_import_runs', 'reverted_at')) {
                    $table->timestamp('reverted_at')->nullable()->after('applied_at');
                }
            });
        }

        if (Schema::hasTable('external_import_items')) {
            Schema::table('external_import_items', function (Blueprint $table): void {
                if (! Schema::hasColumn('external_import_items', 'original_payload_json')) {
                    $table->json('original_payload_json')->nullable()->after('payload_json');
                }
                if (! Schema::hasColumn('external_import_items', 'trace_note')) {
                    $table->text('trace_note')->nullable()->after('original_payload_json');
                }
                if (! Schema::hasColumn('external_import_items', 'revert_status')) {
                    $table->string('revert_status', 40)->nullable()->after('status');
                }
                if (! Schema::hasColumn('external_import_items', 'revert_action')) {
                    $table->string('revert_action', 80)->nullable()->after('revert_status');
                }
                if (! Schema::hasColumn('external_import_items', 'reverted_at')) {
                    $table->timestamp('reverted_at')->nullable()->after('revert_action');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('external_import_items')) {
            Schema::table('external_import_items', function (Blueprint $table): void {
                foreach (['reverted_at', 'revert_action', 'revert_status', 'trace_note', 'original_payload_json'] as $column) {
                    if (Schema::hasColumn('external_import_items', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('external_import_runs')) {
            Schema::table('external_import_runs', function (Blueprint $table): void {
                foreach (['reverted_at', 'revert_summary_json', 'trace_summary_json', 'reverted_by_user_id'] as $column) {
                    if (Schema::hasColumn('external_import_runs', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
