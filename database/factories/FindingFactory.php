<?php

namespace Database\Factories;

use App\Models\Endpoint;
use App\Models\Finding;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Finding> */
class FindingFactory extends Factory
{
    protected $model = Finding::class;

    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'endpoint_id' => Endpoint::factory(),
            'title' => $this->faker->sentence(5),
            'source' => 'manual',
            'severity' => 'medium',
            'status' => 'open',
            'priority' => 'normal',
            'summary' => $this->faker->sentence(12),
            'evidence_required' => true,
            'retest_required' => false,
            'retest_status' => 'not_required',
        ];
    }
}
