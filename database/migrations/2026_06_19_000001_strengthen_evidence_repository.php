<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('finding_evidence')) {
            Schema::table('finding_evidence', function (Blueprint $table): void {
                if (! Schema::hasColumn('finding_evidence', 'repository_status')) {
                    $table->string('repository_status', 40)->default('active')->index();
                }
                if (! Schema::hasColumn('finding_evidence', 'integrity_status')) {
                    $table->string('integrity_status', 40)->default('current')->index();
                }
                if (! Schema::hasColumn('finding_evidence', 'checksum_algorithm')) {
                    $table->string('checksum_algorithm', 40)->default('sha256-v1');
                }
                if (! Schema::hasColumn('finding_evidence', 'repository_notes')) {
                    $table->text('repository_notes')->nullable();
                }
                if (! Schema::hasColumn('finding_evidence', 'reviewed_by_user_id')) {
                    $table->unsignedBigInteger('reviewed_by_user_id')->nullable()->index();
                }
                if (! Schema::hasColumn('finding_evidence', 'reviewed_at')) {
                    $table->timestamp('reviewed_at')->nullable()->index();
                }
                if (! Schema::hasColumn('finding_evidence', 'archived_by_user_id')) {
                    $table->unsignedBigInteger('archived_by_user_id')->nullable()->index();
                }
                if (! Schema::hasColumn('finding_evidence', 'archived_at')) {
                    $table->timestamp('archived_at')->nullable()->index();
                }
            });

            DB::table('finding_evidence')
                ->whereNull('repository_status')
                ->orWhere('repository_status', '')
                ->update(['repository_status' => 'active']);

            DB::table('finding_evidence')
                ->whereNull('integrity_status')
                ->orWhere('integrity_status', '')
                ->update(['integrity_status' => 'current']);

            DB::table('finding_evidence')
                ->whereNull('checksum_algorithm')
                ->orWhere('checksum_algorithm', '')
                ->update(['checksum_algorithm' => 'sha256-v1']);
        }

        if (! Schema::hasTable('evidence_lifecycle_events')) {
            Schema::create('evidence_lifecycle_events', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('project_id')->constrained()->cascadeOnDelete();
                $table->foreignId('finding_evidence_id')->constrained('finding_evidence')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('action', 60)->index();
                $table->string('summary')->nullable();
                $table->json('before_values')->nullable();
                $table->json('after_values')->nullable();
                $table->json('metadata_json')->nullable();
                $table->timestamp('occurred_at')->index();
                $table->timestamps();

                $table->index(['project_id', 'occurred_at']);
                $table->index(['finding_evidence_id', 'occurred_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('evidence_lifecycle_events');
    }
};
