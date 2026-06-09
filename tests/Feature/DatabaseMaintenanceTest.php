<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Services\Database\DatabaseMaintenanceService;
use App\Services\Setup\SetupStateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class DatabaseMaintenanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_database_export_downloads_structured_json_payload(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $response = $this->actingAs($admin)
            ->get(route('settings.database.export'))
            ->assertOk();

        $payload = json_decode($response->streamedContent(), true);

        $this->assertSame(DatabaseMaintenanceService::EXPORT_TYPE, $payload['type']);
        $this->assertSame('Aptoria', $payload['product']);
        $this->assertArrayHasKey('users', $payload['tables']);
        $this->assertArrayHasKey('projects', $payload['tables']);
        $this->assertNotEmpty($payload['database']['schema_hash']);
    }

    public function test_full_database_import_restores_exported_rows_after_schema_validation(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $payload = app(DatabaseMaintenanceService::class)->exportPayload();
        $projectName = Project::query()->firstOrFail()->name;

        Project::query()->delete();
        $this->assertDatabaseMissing('projects', ['name' => $projectName]);

        $file = UploadedFile::fake()->createWithContent('aptoria-database.json', json_encode($payload));

        $this->actingAs($admin)
            ->post(route('settings.database.import'), [
                'database_export' => $file,
                'confirm_import' => 'IMPORT DATABASE',
            ])
            ->assertRedirect(route('settings.index'));

        $this->assertDatabaseHas('projects', ['name' => $projectName]);
    }

    public function test_import_requires_typed_confirmation(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $payload = app(DatabaseMaintenanceService::class)->exportPayload();
        $file = UploadedFile::fake()->createWithContent('aptoria-database.json', json_encode($payload));

        $this->actingAs($admin)
            ->post(route('settings.database.import'), [
                'database_export' => $file,
                'confirm_import' => 'IMPORT',
            ])
            ->assertSessionHasErrors('confirm_import');
    }

    public function test_hard_reset_deletes_application_data_and_removes_setup_lock(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $setupState = app(SetupStateService::class);
        $setupState->writeLock('test');

        $this->actingAs($admin)
            ->post(route('settings.hard-reset'), [
                'confirm_hard_reset' => 'HARD RESET',
            ])
            ->assertRedirect(route('setup.index'));

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('projects', 0);
        $this->assertFalse(File::exists($setupState->lockPath()));
        $this->assertGuest();
    }
}
