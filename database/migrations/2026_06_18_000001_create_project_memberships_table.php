<?php

use App\Models\ProjectMembership;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('project_memberships')) {
            Schema::create('project_memberships', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('project_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('role', 40)->default(ProjectMembership::ROLE_READ_ONLY_VIEWER);
                $table->string('status', 24)->default(ProjectMembership::STATUS_ACTIVE);
                $table->foreignId('invited_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('added_at')->nullable();
                $table->timestamps();
                $table->unique(['project_id', 'user_id']);
                $table->index(['user_id', 'status']);
                $table->index(['project_id', 'role', 'status']);
            });
        }

        if (Schema::hasTable('projects') && Schema::hasTable('users')) {
            DB::table('projects')
                ->whereNotNull('user_id')
                ->orderBy('id')
                ->select(['id', 'user_id'])
                ->get()
                ->each(function (object $project): void {
                    DB::table('project_memberships')->updateOrInsert(
                        ['project_id' => $project->id, 'user_id' => $project->user_id],
                        [
                            'role' => ProjectMembership::ROLE_PROJECT_ADMIN,
                            'status' => ProjectMembership::STATUS_ACTIVE,
                            'invited_by_user_id' => $project->user_id,
                            'added_at' => now(),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('project_memberships');
    }
};
