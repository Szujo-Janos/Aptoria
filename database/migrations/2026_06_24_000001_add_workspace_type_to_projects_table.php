<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('projects')) {
            return;
        }

        if (! Schema::hasColumn('projects', 'workspace_type')) {
            Schema::table('projects', function (Blueprint $table): void {
                $table->string('workspace_type', 20)->default('live')->after('status')->index();
            });
        }

        $this->markLegacySandboxProject('Aptoria Full Demo Project', 'Aptoria Guided Demo Sandbox', 'aptoria-guided-demo-sandbox');
        $this->markLegacySandboxProject('Aptoria Live Demo API Sandbox', 'Aptoria Sandbox API', 'aptoria-sandbox-api');

        DB::table('projects')
            ->whereIn('name', [
                'Aptoria Full Demo Project',
                'Aptoria Guided Demo Sandbox',
                'Aptoria Live Demo API Sandbox',
                'Aptoria Sandbox API',
            ])
            ->update(['workspace_type' => 'sandbox']);
    }

    public function down(): void
    {
        if (! Schema::hasTable('projects') || ! Schema::hasColumn('projects', 'workspace_type')) {
            return;
        }

        Schema::table('projects', function (Blueprint $table): void {
            $table->dropIndex(['workspace_type']);
            $table->dropColumn('workspace_type');
        });
    }

    private function markLegacySandboxProject(string $legacyName, string $newName, string $newSlug): void
    {
        $targetExists = DB::table('projects')->where('slug', $newSlug)->exists();

        $query = DB::table('projects')->where('name', $legacyName);

        if ($targetExists) {
            $query->update(['workspace_type' => 'sandbox']);
            return;
        }

        $query->update([
            'name' => $newName,
            'slug' => $newSlug,
            'workspace_type' => 'sandbox',
        ]);
    }
};
