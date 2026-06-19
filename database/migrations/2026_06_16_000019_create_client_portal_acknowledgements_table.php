<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('client_portal_acknowledgements')) {
            Schema::create('client_portal_acknowledgements', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('project_id')->constrained()->cascadeOnDelete();
                $table->foreignId('client_portal_access_id')->constrained('client_portal_accesses')->cascadeOnDelete();
                $table->foreignId('report_version_id')->nullable()->constrained('report_versions')->nullOnDelete();
                $table->string('decision_status')->default('reviewed');
                $table->string('acknowledged_by_name');
                $table->string('acknowledged_by_email')->nullable();
                $table->text('comment')->nullable();
                $table->boolean('acknowledge_terms')->default(true);
                $table->json('evidence_summary_json')->nullable();
                $table->timestamp('acknowledged_at');
                $table->string('ip_address', 80)->nullable();
                $table->string('user_agent', 500)->nullable();
                $table->timestamps();

                $table->index(['project_id', 'decision_status']);
                $table->index(['client_portal_access_id', 'acknowledged_at']);
                $table->index(['report_version_id', 'decision_status']);
            });
        }

        if (Schema::hasTable('client_portal_accesses')) {
            Schema::table('client_portal_accesses', function (Blueprint $table): void {
                if (! Schema::hasColumn('client_portal_accesses', 'acknowledgement_status')) {
                    $table->string('acknowledgement_status')->default('pending')->after('acknowledge_required');
                }
                if (! Schema::hasColumn('client_portal_accesses', 'acknowledgement_decision')) {
                    $table->string('acknowledgement_decision')->nullable()->after('acknowledgement_status');
                }
                if (! Schema::hasColumn('client_portal_accesses', 'acknowledgement_comment')) {
                    $table->text('acknowledgement_comment')->nullable()->after('acknowledgement_decision');
                }
                if (! Schema::hasColumn('client_portal_accesses', 'latest_acknowledgement_id')) {
                    $table->foreignId('latest_acknowledgement_id')->nullable()->after('acknowledgement_comment')->constrained('client_portal_acknowledgements')->nullOnDelete();
                }
            });

            DB::table('client_portal_accesses')
                ->where('acknowledge_required', false)
                ->whereNull('acknowledged_at')
                ->update(['acknowledgement_status' => 'not_required']);

            DB::table('client_portal_accesses')
                ->whereNotNull('acknowledged_at')
                ->update([
                    'acknowledgement_status' => 'acknowledged',
                    'acknowledgement_decision' => 'reviewed',
                ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('client_portal_accesses')) {
            Schema::table('client_portal_accesses', function (Blueprint $table): void {
                if (Schema::hasColumn('client_portal_accesses', 'latest_acknowledgement_id')) {
                    $table->dropConstrainedForeignId('latest_acknowledgement_id');
                }
                foreach (['acknowledgement_comment', 'acknowledgement_decision', 'acknowledgement_status'] as $column) {
                    if (Schema::hasColumn('client_portal_accesses', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        Schema::dropIfExists('client_portal_acknowledgements');
    }
};
