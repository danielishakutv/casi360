<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Forum;
use Illuminate\Database\Eloquent\Factories\Factory;

class ForumFactory extends Factory
{
    protected $model = Forum::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'description' => fake()->sentence(),
            'type' => 'general',
            'department_id' => null,
            'status' => 'active',
        ];
    }

    public function department(?Department $department = null): static
    {
        return $this->state(fn () => [
            'type' => 'department',
            'department_id' => $department?->id ?? Department::factory(),
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn () => ['status' => 'archived']);
    }
}
