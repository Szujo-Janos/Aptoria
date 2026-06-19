<?php

namespace Database\Factories;

use App\Models\Finding;
use App\Models\FindingEvidence;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FindingEvidence> */
class FindingEvidenceFactory extends Factory
{
    protected $model = FindingEvidence::class;

    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'finding_id' => Finding::factory(),
            'type' => 'note',
            'title' => $this->faker->sentence(4),
            'source_label' => 'Manual QA',
            'content' => $this->faker->sentence(12),
            'captured_at' => now(),
            'sha256' => hash('sha256', $this->faker->uuid()),
        ];
    }
}
