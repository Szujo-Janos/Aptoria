<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('client_portal_accesses')) {
            return;
        }

        Schema::create('client_portal_accesses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('report_version_id')->nullable()->constrained('report_versions')->nullOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('token', 96)->unique();
            $table->string('role')->default('client_viewer');
            $table->json('permissions_json')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('acknowledge_required')->default(false);
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_viewed_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->string('acknowledged_by_name')->nullable();
            $table->string('acknowledged_by_email')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'is_active']);
            $table->index(['project_id', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_portal_accesses');
    }
};
