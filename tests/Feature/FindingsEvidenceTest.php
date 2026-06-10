<?php

namespace Tests\Feature;

use App\Models\Endpoint;
use App\Models\Finding;
use App\Models\FindingEvidence;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FindingsEvidenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_finding_can_be_created_with_evidence(): void
    {
        $user = User::query()->create([
            'name' => 'QA Admin',
            'email' => 'qa@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $project = Project::query()->create([
            'user_id' => $user->id,
            'name' => 'QA API',
            'slug' => 'qa-api',
            'base_url' => 'https://api.example.test',
            'is_active' => true,
        ]);

        $endpoint = Endpoint::query()->create([
            'project_id' => $project->id,
            'method' => Endpoint::METHOD_GET,
            'path' => '/health',
            'name' => 'Health',
            'auth_required' => false,
            'expected_status' => 200,
            'risk_level' => Endpoint::RISK_LOW,
            'is_active' => true,
            'excluded_from_scan' => false,
        ]);

        $finding = Finding::query()->create([
            'project_id' => $project->id,
            'endpoint_id' => $endpoint->id,
            'title' => 'Health endpoint contract mismatch',
            'source' => Finding::SOURCE_CONTRACT,
            'severity' => Finding::SEVERITY_HIGH,
            'status' => Finding::STATUS_OPEN,
            'expected_result' => 'HTTP 200 JSON',
            'actual_result' => 'HTTP 500',
        ]);

        $evidence = FindingEvidence::query()->create([
            'project_id' => $project->id,
            'finding_id' => $finding->id,
            'type' => FindingEvidence::TYPE_HTTP,
            'source_label' => 'Stored scan response',
            'content' => 'HTTP 500 returned during QA scan.',
        ]);

        $this->assertTrue($finding->fresh()->is_open);
        $this->assertSame($endpoint->id, $finding->fresh()->endpoint->id);
        $this->assertSame($evidence->id, $finding->fresh()->evidence()->first()->id);
    }

    public function test_admin_can_attach_file_and_request_response_evidence_to_finding(): void
    {
        Storage::fake('local');

        $admin = User::query()->create([
            'name' => 'Evidence Admin',
            'email' => 'evidence@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $project = Project::query()->create([
            'user_id' => $admin->id,
            'name' => 'Evidence API',
            'slug' => 'evidence-api',
            'base_url' => 'https://api.example.test',
            'is_active' => true,
        ]);

        $finding = Finding::query()->create([
            'project_id' => $project->id,
            'title' => 'Unauthenticated user data exposure',
            'source' => Finding::SOURCE_SCAN,
            'severity' => Finding::SEVERITY_CRITICAL,
            'status' => Finding::STATUS_CONFIRMED,
        ]);

        $this->actingAs($admin)
            ->post(route('projects.findings.evidence.store', [$project, $finding]), [
                'type' => FindingEvidence::TYPE_REQUEST_RESPONSE,
                'source_label' => 'Manual reproduction evidence',
                'content' => 'Unauthenticated request returned user profile fields.',
                'request_excerpt' => "GET /api/users/1 HTTP/1.1\nHost: api.example.test",
                'response_excerpt' => "HTTP/1.1 200 OK\n{\"email\":\"user@example.test\"}",
                'curl_command' => 'curl -i https://api.example.test/api/users/1',
                'captured_at' => '2026-06-10T12:30',
                'attachment' => UploadedFile::fake()->createWithContent('response.json', '{"email":"user@example.test"}'),
            ])
            ->assertRedirect(route('projects.findings.show', [$project, $finding]));

        $evidence = $finding->fresh()->evidence()->firstOrFail();

        $this->assertSame(FindingEvidence::TYPE_REQUEST_RESPONSE, $evidence->type);
        $this->assertSame($admin->id, $evidence->captured_by_user_id);
        $this->assertSame('response.json', $evidence->attachment_original_name);
        $this->assertNotNull($evidence->attachment_sha256);
        Storage::disk('local')->assertExists($evidence->attachment_path);

        $this->actingAs($admin)
            ->get(route('projects.findings.show', [$project, $finding]))
            ->assertOk()
            ->assertSee('Manual reproduction evidence')
            ->assertSee('Request excerpt')
            ->assertSee('Response excerpt')
            ->assertSee('Download attachment');
    }

    public function test_evidence_attachment_can_be_downloaded_and_deleted_with_file_cleanup(): void
    {
        Storage::fake('local');

        $admin = User::query()->create([
            'name' => 'Evidence Admin',
            'email' => 'evidence-delete@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $project = Project::query()->create([
            'user_id' => $admin->id,
            'name' => 'Evidence API',
            'slug' => 'evidence-api-delete',
            'base_url' => 'https://api.example.test',
            'is_active' => true,
        ]);

        $finding = Finding::query()->create([
            'project_id' => $project->id,
            'title' => 'Evidence cleanup finding',
            'source' => Finding::SOURCE_MANUAL,
            'severity' => Finding::SEVERITY_HIGH,
            'status' => Finding::STATUS_OPEN,
        ]);

        $path = 'private/finding-evidence/'.$project->id.'/'.$finding->id.'/proof.txt';
        Storage::disk('local')->put($path, 'proof body');

        $evidence = FindingEvidence::query()->create([
            'project_id' => $project->id,
            'finding_id' => $finding->id,
            'type' => FindingEvidence::TYPE_FILE,
            'source_label' => 'Uploaded proof',
            'content' => 'Attached text file.',
            'attachment_disk' => 'local',
            'attachment_path' => $path,
            'attachment_original_name' => 'proof.txt',
            'attachment_mime_type' => 'text/plain',
            'attachment_size' => 10,
        ]);

        $this->actingAs($admin)
            ->get(route('projects.findings.evidence.download', [$project, $finding, $evidence]))
            ->assertOk();

        $this->actingAs($admin)
            ->delete(route('projects.findings.evidence.destroy', [$project, $finding, $evidence]))
            ->assertRedirect(route('projects.findings.show', [$project, $finding]));

        $this->assertDatabaseMissing('finding_evidence', ['id' => $evidence->id]);
        Storage::disk('local')->assertMissing($path);
    }
}
