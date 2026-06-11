<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_portal_acknowledgements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_portal_access_id')->constrained()->cascadeOnDelete();
            $table->foreignId('report_version_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('release_decision_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('risk_acceptance_id')->nullable()->constrained()->nullOnDelete();
            $table->string('acknowledgement_type', 60)->index();
            $table->string('actor_name', 160)->nullable();
            $table->string('actor_email', 190)->nullable();
            $table->text('note')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'acknowledgement_type']);
            $table->index(['client_portal_access_id', 'acknowledged_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_portal_acknowledgements');
    }
};
