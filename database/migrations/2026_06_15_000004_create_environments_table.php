<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('environments')) {
            Schema::create('environments', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('project_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('base_url', 500);
                $table->string('environment_type', 40)->default('dev');
                $table->boolean('is_production')->default(false);
                $table->boolean('is_default')->default(false);
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['project_id', 'is_default']);
                $table->index(['project_id', 'environment_type']);
            });

            return;
        }

        Schema::table('environments', function (Blueprint $table): void {
            if (! Schema::hasColumn('environments', 'project_id')) {
                $table->foreignId('project_id')->nullable()->constrained()->cascadeOnDelete();
            }
            if (! Schema::hasColumn('environments', 'name')) {
                $table->string('name')->default('Default');
            }
            if (! Schema::hasColumn('environments', 'base_url')) {
                $table->string('base_url', 500)->nullable();
            }
            if (! Schema::hasColumn('environments', 'environment_type')) {
                $table->string('environment_type', 40)->default('dev');
            }
            if (! Schema::hasColumn('environments', 'is_production')) {
                $table->boolean('is_production')->default(false);
            }
            if (! Schema::hasColumn('environments', 'is_default')) {
                $table->boolean('is_default')->default(false);
            }
            if (! Schema::hasColumn('environments', 'notes')) {
                $table->text('notes')->nullable();
            }
            if (! Schema::hasColumn('environments', 'created_at')) {
                $table->timestamps();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('environments');
    }
};
