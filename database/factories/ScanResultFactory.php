<?php

namespace Database\Factories;

use App\Models\Endpoint;
use App\Models\Project;
use App\Models\ScanResult;
use App\Models\ScanRun;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ScanResult> */
class ScanResultFactory extends Factory
{
    protected $model = ScanResult::class;

    public function definition(): array
    {
        return [
            'scan_run_id' => ScanRun::factory(),
            'project_id' => Project::factory(),
            'endpoint_id' => Endpoint::factory(),
            'method' => 'GET',
            'url' => 'https://api.example.test/health',
            'status' => 'passed',
            'status_code' => 200,
            'response_time_ms' => 120,
            'content_type' => 'application/json',
            'response_size' => 64,
            'headers_json' => ['content-type' => ['application/json']],
            'body_preview' => '{"ok":true}',
            'expected_status_matched' => true,
            'expected_content_type_matched' => true,
            'risk_level' => 'low',
            'risk_reason' => 'Response matched the safe scan baseline.',
        ];
    }
}
