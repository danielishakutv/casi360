<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        return [
            'project_code' => 'PRJ-' . fake()->unique()->numerify('####'),
            'name' => fake()->catchPhrase(),
            'description' => fake()->paragraph(),
            'objectives' => fake()->paragraph(),
            'department_id' => Department::factory(),
            'project_manager_id' => Employee::factory(),
            'start_date' => fake()->dateTimeBetween('-6 months', 'now'),
            'end_date' => fake()->dateTimeBetween('+3 months', '+2 years'),
            'location' => fake()->city(),
            'total_budget' => fake()->randomFloat(2, 50000, 5000000),
            'currency' => 'NGN',
            'status' => 'active',
            'notes' => fake()->sentence(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => 'draft']);
    }

    public function completed(): static
    {
        return $this->state(fn () => ['status' => 'completed']);
    }
}
