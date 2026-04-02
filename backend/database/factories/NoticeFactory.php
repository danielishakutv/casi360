<?php

namespace Database\Factories;

use App\Models\Notice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class NoticeFactory extends Factory
{
    protected $model = Notice::class;

    public function definition(): array
    {
        return [
            'author_id' => User::factory(),
            'title' => fake()->sentence(6),
            'body' => fake()->paragraphs(3, true),
            'priority' => fake()->randomElement(['normal', 'important', 'critical']),
            'status' => 'published',
            'publish_date' => now(),
            'expiry_date' => fake()->dateTimeBetween('+7 days', '+30 days'),
            'is_pinned' => false,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => 'draft']);
    }

    public function pinned(): static
    {
        return $this->state(fn () => ['is_pinned' => true]);
    }
}
