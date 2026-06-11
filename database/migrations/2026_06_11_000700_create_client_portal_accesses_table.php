<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_portal_accesses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('label', 160);
            $table->string('contact_name', 160)->nullable();
            $table->string('contact_email', 190)->nullable();
            $table->string('role', 40)->default('client_viewer')->index();
            $table->string('status', 30)->default('active')->index();
            $table->string('portal_token', 96)->unique();
            $table->json('permissions')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('last_viewed_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'status']);
            $table->index(['project_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_portal_accesses');
    }
};
