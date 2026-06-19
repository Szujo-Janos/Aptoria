<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('auth_profiles')) {
            Schema::create('auth_profiles', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('project_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('type', 40)->default('none');
                $table->text('encrypted_token')->nullable();
                $table->string('username')->nullable();
                $table->text('encrypted_password')->nullable();
                $table->string('header_name')->nullable();
                $table->text('encrypted_header_value')->nullable();
                $table->boolean('is_default')->default(false);
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['project_id', 'is_default']);
                $table->index(['project_id', 'type']);
            });

            return;
        }

        Schema::table('auth_profiles', function (Blueprint $table): void {
            if (! Schema::hasColumn('auth_profiles', 'project_id')) {
                $table->foreignId('project_id')->nullable()->constrained()->cascadeOnDelete();
            }
            if (! Schema::hasColumn('auth_profiles', 'name')) {
                $table->string('name')->default('Default auth');
            }
            if (! Schema::hasColumn('auth_profiles', 'type')) {
                $table->string('type', 40)->default('none');
            }
            if (! Schema::hasColumn('auth_profiles', 'encrypted_token')) {
                $table->text('encrypted_token')->nullable();
            }
            if (! Schema::hasColumn('auth_profiles', 'username')) {
                $table->string('username')->nullable();
            }
            if (! Schema::hasColumn('auth_profiles', 'encrypted_password')) {
                $table->text('encrypted_password')->nullable();
            }
            if (! Schema::hasColumn('auth_profiles', 'header_name')) {
                $table->string('header_name')->nullable();
            }
            if (! Schema::hasColumn('auth_profiles', 'encrypted_header_value')) {
                $table->text('encrypted_header_value')->nullable();
            }
            if (! Schema::hasColumn('auth_profiles', 'is_default')) {
                $table->boolean('is_default')->default(false);
            }
            if (! Schema::hasColumn('auth_profiles', 'notes')) {
                $table->text('notes')->nullable();
            }
            if (! Schema::hasColumn('auth_profiles', 'created_at')) {
                $table->timestamps();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auth_profiles');
    }
};
