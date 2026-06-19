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
            if (! Schema::hasColumn('report_versions', 'review_note')) {
                $table->text('review_note')->nullable()->after('notes');
            }
            if (! Schema::hasColumn('report_versions', 'approval_note')) {
                $table->text('approval_note')->nullable()->after('review_note');
            }
            if (! Schema::hasColumn('report_versions', 'archive_note')) {
                $table->text('archive_note')->nullable()->after('approval_note');
            }
            if (! Schema::hasColumn('report_versions', 'approval_signoff_name')) {
                $table->string('approval_signoff_name')->nullable()->after('archive_note');
            }
            if (! Schema::hasColumn('report_versions', 'approval_signoff_role')) {
                $table->string('approval_signoff_role')->nullable()->after('approval_signoff_name');
            }
            if (! Schema::hasColumn('report_versions', 'approval_signoff_statement')) {
                $table->text('approval_signoff_statement')->nullable()->after('approval_signoff_role');
            }
            if (! Schema::hasColumn('report_versions', 'approval_signed_at')) {
                $table->timestamp('approval_signed_at')->nullable()->after('approval_signoff_statement');
            }
            if (! Schema::hasColumn('report_versions', 'approval_context_json')) {
                $table->json('approval_context_json')->nullable()->after('approval_signed_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('report_versions')) {
            return;
        }

        Schema::table('report_versions', function (Blueprint $table): void {
            foreach ([
                'approval_context_json',
                'approval_signed_at',
                'approval_signoff_statement',
                'approval_signoff_role',
                'approval_signoff_name',
                'archive_note',
                'approval_note',
                'review_note',
            ] as $column) {
                if (Schema::hasColumn('report_versions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
