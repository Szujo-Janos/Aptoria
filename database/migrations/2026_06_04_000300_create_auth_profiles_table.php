<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auth_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('type', 40)->default('none');
            $table->text('encrypted_token')->nullable();
            $table->string('username')->nullable();
            $table->text('encrypted_password')->nullable();
            $table->string('header_name')->nullable();
            $table->text('encrypted_header_value')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->unique(['project_id', 'name']);
            $table->index(['project_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auth_profiles');
    }
};
