<?php

namespace Database\Factories;

use App\Models\Endpoint;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Endpoint> */
class EndpointFactory extends Factory
{
    protected $model = Endpoint::class;

    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'method' => 'GET',
            'path' => '/api/'.$this->faker->slug(),
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'tags' => 'smoke, regression',
            'auth_required' => false,
            'expected_status' => 200,
            'expected_content_type' => 'application/json',
            'risk_level' => 'low',
            'is_active' => true,
            'excluded_from_scan' => false,
            'notes' => null,
        ];
    }
}
