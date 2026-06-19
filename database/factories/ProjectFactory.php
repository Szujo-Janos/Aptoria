<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Project> */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        $name = $this->faker->words(3, true);

        return [
            'user_id' => User::factory(),
            'name' => Str::headline($name),
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(5)),
            'description' => $this->faker->sentence(12),
            'base_url' => 'https://api.example.test',
            'environment_label' => 'staging',
            'status' => 'active',
            'qa_owner' => $this->faker->name(),
            'release_goal' => $this->faker->sentence(10),
            'is_active' => true,
        ];
    }
}
