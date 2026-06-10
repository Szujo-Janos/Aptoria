<?php

namespace Database\Seeders;

use App\Models\Endpoint;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;
use App\Services\Settings\ProjectSettingService;
use App\Services\Settings\SettingService;
use App\Services\Calendar\CalendarActivityLogger;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        CalendarActivityLogger::withoutRecording(function (): void {
        app(SettingService::class)->seedDefaults();
            $projectSettings = app(ProjectSettingService::class);
    
            $adminEmail = config('aptoria.default_admin.email', 'admin@example.com');
            $adminPassword = config('aptoria.default_admin.password', 'change-me-now');
    
            $admin = User::query()->firstOrCreate(
                ['email' => $adminEmail],
                [
                    'name' => 'Aptoria Admin',
                    'password' => Hash::make($adminPassword),
                    'role' => 'admin',
                ]
            );
    
            $project = Project::query()->firstOrCreate(
                ['slug' => 'demo-public-api'],
                [
                    'user_id' => $admin->id,
                    'name' => 'Demo Public API',
                    'description' => 'Demo project for Aptoria safe GET/HEAD probes. Uses a public demo API with harmless GET endpoints.',
                    'base_url' => 'https://jsonplaceholder.typicode.com',
                    'is_active' => true,
                ]
            );
    
            $project->environments()->firstOrCreate(
                ['name' => 'staging'],
                [
                    'base_url' => 'https://jsonplaceholder.typicode.com',
                    'environment_type' => \App\Models\Environment::TYPE_STAGING,
                    'is_production' => false,
                ]
            );
    
            $project->environments()->firstOrCreate(
                ['name' => 'production'],
                [
                    'base_url' => $project->base_url,
                    'environment_type' => \App\Models\Environment::TYPE_PRODUCTION,
                    'is_production' => true,
                ]
            );
    
            $noAuth = $project->authProfiles()->firstOrCreate(
                ['name' => 'No Auth'],
                [
                    'type' => 'none',
                    'is_default' => true,
                    'notes' => 'Default no-auth safe probe profile.',
                ]
            );
    
            $projectSettings->seedDefaults($project);
    
            $staging = $project->environments()->where('name', 'staging')->first();
    
            $project->endpoints()->firstOrCreate(
                ['method' => Endpoint::METHOD_GET, 'path' => '/todos/1'],
                [
                    'environment_id' => $staging?->id,
                    'auth_profile_id' => $noAuth->id,
                    'name' => 'Demo todo item',
                    'description' => 'Simple public GET endpoint used to verify the safe probe engine.',
                    'tags' => 'demo, public, safe-probe',
                    'auth_required' => false,
                    'expected_status' => 200,
                    'expected_content_type' => 'application/json',
                    'risk_level' => Endpoint::RISK_LOW,
                    'risk_reason' => 'Documented public demo endpoint. Confirm the response schema remains stable.',
                    'is_active' => true,
                ]
            );
    
            $project->endpoints()->firstOrCreate(
                ['method' => Endpoint::METHOD_GET, 'path' => '/users/1'],
                [
                    'environment_id' => $staging?->id,
                    'auth_profile_id' => $noAuth->id,
                    'name' => 'Demo user detail',
                    'description' => 'Public demo endpoint intentionally marked for review because the path suggests user data.',
                    'tags' => 'users, regression, review',
                    'auth_required' => false,
                    'expected_status' => 200,
                    'expected_content_type' => 'application/json',
                    'risk_level' => Endpoint::RISK_HIGH,
                    'risk_reason' => 'User-related endpoint name. Verify that this is only a public demo response and does not represent real customer data.',
                    'is_active' => true,
                ]
            );
        });

    }
}
