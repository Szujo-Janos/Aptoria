<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProjectReportBrandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_report_branding_overrides_profile_identity_in_reports(): void
    {
        $admin = User::query()->create([
            'name' => 'Global Admin',
            'email' => 'branding-admin@example.com',
            'password' => 'password',
            'role' => 'admin',
            'report_display_name' => 'Global QA Name',
            'report_organization' => 'Global QA Org',
        ]);

        $project = Project::query()->create([
            'user_id' => $admin->id,
            'name' => 'Branded Client API',
            'slug' => 'branded-client-api',
            'base_url' => 'https://api.client.test',
            'report_client_name' => 'Client Alpha',
            'report_organization' => 'Alpha Ltd.',
            'report_prepared_by' => 'Project QA Lead',
            'report_role_title' => 'API Audit Lead',
            'report_confidentiality_label' => 'Client Confidential',
            'report_disclaimer' => 'Prepared for scoped QA review only.',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('projects.reports.executive.markdown', $project))
            ->assertOk()
            ->assertSee('**Client:** Client Alpha', false)
            ->assertSee('**Organization / client:** Alpha Ltd.', false)
            ->assertSee('**Prepared by:** Project QA Lead', false)
            ->assertSee('**Role / title:** API Audit Lead', false)
            ->assertSee('**Confidentiality:** Client Confidential', false)
            ->assertSee('Prepared for scoped QA review only.', false)
            ->assertDontSee('Global QA Org', false);

        $this->actingAs($admin)
            ->get(route('projects.reports.executive.html', $project))
            ->assertOk()
            ->assertSee('Client Alpha', false)
            ->assertSee('Alpha Ltd.', false)
            ->assertSee('Project QA Lead', false)
            ->assertSee('Client Confidential', false)
            ->assertSee('Prepared for scoped QA review only.', false);
    }

    public function test_project_form_persists_report_logo_and_branding_fields(): void
    {
        Storage::fake('local');

        $admin = User::query()->create([
            'name' => 'Logo Admin',
            'email' => 'branding-logo@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->post(route('projects.store'), [
            'name' => 'Logo Client API',
            'base_url' => 'https://logo-client.test',
            'description' => 'Logo branding project.',
            'report_client_name' => 'Logo Client',
            'report_organization' => 'Logo Org',
            'report_prepared_by' => 'Logo QA',
            'report_role_title' => 'Report Owner',
            'report_confidentiality_label' => 'Internal Draft',
            'report_disclaimer' => 'Logo report disclaimer.',
            'report_logo' => $this->fakePngUpload('client-logo.png'),
            'is_active' => '1',
        ]);

        $response->assertRedirect();

        $project = Project::query()->where('name', 'Logo Client API')->firstOrFail();
        $this->assertSame('Logo Client', $project->report_client_name);
        $this->assertSame('Logo Org', $project->report_organization);
        $this->assertSame('Logo QA', $project->report_prepared_by);
        $this->assertSame('client-logo.png', $project->report_logo_original_name);
        $this->assertNotNull($project->report_logo_path);
        Storage::disk('local')->assertExists((string) $project->report_logo_path);

        $this->actingAs($admin)
            ->get(route('projects.reports.executive.html', $project))
            ->assertOk()
            ->assertSee('data:image/png;base64', false)
            ->assertSee('Logo Client', false)
            ->assertSee('Internal Draft', false);
    }

    private function fakePngUpload(string $name): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'aptoria-logo-');
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII=');
        file_put_contents((string) $path, $png);

        return new UploadedFile((string) $path, $name, 'image/png', null, true);
    }
}
