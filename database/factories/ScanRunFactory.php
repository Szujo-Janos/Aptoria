<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\ScanRun;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ScanRun> */
class ScanRunFactory extends Factory
{
    protected $model = ScanRun::class;

    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'profile' => 'safe',
            'status' => 'completed',
            'started_at' => now()->subMinute(),
            'completed_at' => now(),
            'duration_ms' => 250,
            'summary_json' => ['total' => 1, 'passed' => 1, 'warning' => 0, 'failed' => 0, 'skipped' => 0],
        ];
    }
}
