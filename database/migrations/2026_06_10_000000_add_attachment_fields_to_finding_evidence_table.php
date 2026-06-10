<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finding_evidence', function (Blueprint $table): void {
            if (! Schema::hasColumn('finding_evidence', 'attachment_disk')) {
                $table->string('attachment_disk', 80)->nullable()->after('url');
            }
            if (! Schema::hasColumn('finding_evidence', 'attachment_path')) {
                $table->string('attachment_path', 1000)->nullable()->after('attachment_disk');
            }
            if (! Schema::hasColumn('finding_evidence', 'attachment_original_name')) {
                $table->string('attachment_original_name', 255)->nullable()->after('attachment_path');
            }
            if (! Schema::hasColumn('finding_evidence', 'attachment_mime_type')) {
                $table->string('attachment_mime_type', 160)->nullable()->after('attachment_original_name');
            }
            if (! Schema::hasColumn('finding_evidence', 'attachment_size')) {
                $table->unsignedBigInteger('attachment_size')->nullable()->after('attachment_mime_type');
            }
            if (! Schema::hasColumn('finding_evidence', 'attachment_sha256')) {
                $table->string('attachment_sha256', 64)->nullable()->after('attachment_size');
            }
            if (! Schema::hasColumn('finding_evidence', 'request_excerpt')) {
                $table->longText('request_excerpt')->nullable()->after('content');
            }
            if (! Schema::hasColumn('finding_evidence', 'response_excerpt')) {
                $table->longText('response_excerpt')->nullable()->after('request_excerpt');
            }
            if (! Schema::hasColumn('finding_evidence', 'curl_command')) {
                $table->longText('curl_command')->nullable()->after('response_excerpt');
            }
            if (! Schema::hasColumn('finding_evidence', 'captured_at')) {
                $table->timestamp('captured_at')->nullable()->after('metadata_json');
            }
            if (! Schema::hasColumn('finding_evidence', 'captured_by_user_id')) {
                $table->foreignId('captured_by_user_id')->nullable()->after('captured_at')->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('finding_evidence', function (Blueprint $table): void {
            if (Schema::hasColumn('finding_evidence', 'captured_by_user_id')) {
                $table->dropConstrainedForeignId('captured_by_user_id');
            }

            foreach ([
                'captured_at',
                'curl_command',
                'response_excerpt',
                'request_excerpt',
                'attachment_sha256',
                'attachment_size',
                'attachment_mime_type',
                'attachment_original_name',
                'attachment_path',
                'attachment_disk',
            ] as $column) {
                if (Schema::hasColumn('finding_evidence', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
