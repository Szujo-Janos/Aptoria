<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('external_import_items')) {
            return;
        }

        Schema::table('external_import_items', function (Blueprint $table): void {
            if (! Schema::hasColumn('external_import_items', 'match_status')) {
                $table->string('match_status', 40)->default('new')->after('action');
            }
            if (! Schema::hasColumn('external_import_items', 'apply_strategy')) {
                $table->string('apply_strategy', 40)->default('create')->after('match_status');
            }
            if (! Schema::hasColumn('external_import_items', 'conflict_reason')) {
                $table->text('conflict_reason')->nullable()->after('apply_strategy');
            }
            if (! Schema::hasColumn('external_import_items', 'target_type')) {
                $table->string('target_type', 80)->nullable()->after('finding_id');
            }
            if (! Schema::hasColumn('external_import_items', 'target_id')) {
                $table->unsignedBigInteger('target_id')->nullable()->after('target_type');
            }
            if (! Schema::hasColumn('external_import_items', 'normalized_key')) {
                $table->string('normalized_key', 1200)->nullable()->after('external_key');
            }
            if (! Schema::hasColumn('external_import_items', 'source_hash')) {
                $table->string('source_hash', 64)->nullable()->after('normalized_key');
            }
            if (! Schema::hasColumn('external_import_items', 'created_record_type')) {
                $table->string('created_record_type', 80)->nullable()->after('applied_at');
            }
            if (! Schema::hasColumn('external_import_items', 'created_record_id')) {
                $table->unsignedBigInteger('created_record_id')->nullable()->after('created_record_type');
            }
            if (! Schema::hasColumn('external_import_items', 'updated_record_type')) {
                $table->string('updated_record_type', 80)->nullable()->after('created_record_id');
            }
            if (! Schema::hasColumn('external_import_items', 'updated_record_id')) {
                $table->unsignedBigInteger('updated_record_id')->nullable()->after('updated_record_type');
            }
        });

        Schema::table('external_import_items', function (Blueprint $table): void {
            try { $table->index(['external_import_run_id', 'match_status'], 'external_import_items_run_match_status_index'); } catch (Throwable) {}
            try { $table->index(['project_id', 'match_status'], 'external_import_items_project_match_status_index'); } catch (Throwable) {}
            try { $table->index(['target_type', 'target_id'], 'external_import_items_target_index'); } catch (Throwable) {}
            try { $table->index('source_hash', 'external_import_items_source_hash_index'); } catch (Throwable) {}
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('external_import_items')) {
            return;
        }

        Schema::table('external_import_items', function (Blueprint $table): void {
            foreach (['external_import_items_source_hash_index', 'external_import_items_target_index', 'external_import_items_project_match_status_index', 'external_import_items_run_match_status_index'] as $index) {
                try { $table->dropIndex($index); } catch (Throwable) {}
            }
        });

        Schema::table('external_import_items', function (Blueprint $table): void {
            foreach (['updated_record_id', 'updated_record_type', 'created_record_id', 'created_record_type', 'source_hash', 'normalized_key', 'target_id', 'target_type', 'conflict_reason', 'apply_strategy', 'match_status'] as $column) {
                if (Schema::hasColumn('external_import_items', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
