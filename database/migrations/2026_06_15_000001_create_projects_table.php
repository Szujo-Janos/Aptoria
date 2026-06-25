<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('projects')) {
            Schema::create('projects', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->string('base_url', 500)->nullable();
                $table->string('environment_label')->nullable();
                $table->string('status')->default('draft');
                $table->string('workspace_type', 20)->default('live')->index();
                $table->string('qa_owner')->nullable();
                $table->text('release_goal')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
            return;
        }

        Schema::table('projects', function (Blueprint $table): void {
            if (! Schema::hasColumn('projects', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('id');
            }
            if (! Schema::hasColumn('projects', 'slug')) {
                $table->string('slug')->nullable()->after('name');
            }
            if (! Schema::hasColumn('projects', 'base_url')) {
                $table->string('base_url', 500)->nullable()->after('description');
            }
            if (! Schema::hasColumn('projects', 'environment_label')) {
                $table->string('environment_label')->nullable()->after('base_url');
            }
            if (! Schema::hasColumn('projects', 'status')) {
                $table->string('status')->default('draft')->after('environment_label');
            }
            if (! Schema::hasColumn('projects', 'workspace_type')) {
                $table->string('workspace_type', 20)->default('live')->after('status')->index();
            }
            if (! Schema::hasColumn('projects', 'qa_owner')) {
                $table->string('qa_owner')->nullable()->after('status');
            }
            if (! Schema::hasColumn('projects', 'release_goal')) {
                $table->text('release_goal')->nullable()->after('qa_owner');
            }
            if (! Schema::hasColumn('projects', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('release_goal');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
