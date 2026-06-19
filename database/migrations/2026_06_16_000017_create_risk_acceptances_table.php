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
            $table->foreignId('revoked_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('renewed_from_id')->nullable()->constrained('risk_acceptances')->nullOnDelete();
            $table->string('status')->default('active');
            $table->timestamp('accepted_at')->nullable();
            $table->date('accepted_until')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->text('reason');
            $table->text('business_justification')->nullable();
            $table->text('mitigation_note')->nullable();
            $table->string('release_scope')->nullable();
            $table->json('metadata_json')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'status']);
            $table->index(['finding_id', 'status']);
            $table->index('accepted_until');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_acceptances');
    }
};
