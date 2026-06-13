<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_memberships', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invited_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('role', 60)->index();
            $table->text('notes')->nullable();
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'user_id']);
            $table->index(['user_id', 'role']);
        });

        if (Schema::hasTable('projects') && Schema::hasTable('users')) {
            DB::table('projects')
                ->whereNotNull('user_id')
                ->orderBy('id')
                ->select(['id', 'user_id', 'created_at', 'updated_at'])
                ->get()
                ->each(function (object $project): void {
                    DB::table('project_memberships')->insertOrIgnore([
                        'project_id' => $project->id,
                        'user_id' => $project->user_id,
                        'role' => 'project_admin',
                        'joined_at' => $project->created_at ?? now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('project_memberships');
    }
};
