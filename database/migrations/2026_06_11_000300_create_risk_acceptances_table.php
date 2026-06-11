<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risk_acceptances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('finding_id')->constrained()->cascadeOnDelete();
            $table->foreignId('accepted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('renewed_from_id')->nullable()->constrained('risk_acceptances')->nullOnDelete();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('accepted_until')->nullable();
            $table->string('status', 40)->default('active')->index();
            $table->text('reason');
            $table->text('business_justification')->nullable();
            $table->text('mitigation_note')->nullable();
            $table->text('evidence_requirement')->nullable();
            $table->string('release_scope')->nullable();
            $table->string('expiry_action', 80)->default('review');
            $table->json('metadata_json')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'status']);
            $table->index(['project_id', 'accepted_until']);
            $table->index(['finding_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_acceptances');
    }
};
