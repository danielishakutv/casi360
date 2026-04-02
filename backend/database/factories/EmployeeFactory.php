<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        return [
            'staff_id' => 'EMP-' . fake()->unique()->numerify('####'),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'department_id' => Department::factory(),
            'designation_id' => Designation::factory(),
            'manager' => fake()->name(),
            'status' => 'active',
            'join_date' => fake()->dateTimeBetween('-3 years', 'now'),
            'salary' => fake()->randomFloat(2, 30000, 150000),
            'gender' => fake()->randomElement(['male', 'female']),
            'date_of_birth' => fake()->dateTimeBetween('-50 years', '-20 years'),
            'address' => fake()->address(),
            'emergency_contact_name' => fake()->name(),
            'emergency_contact_phone' => fake()->phoneNumber(),
        ];
    }

    public function terminated(): static
    {
        return $this->state(fn () => [
            'status' => 'terminated',
            'termination_date' => now(),
        ]);
    }

    public function onLeave(): static
    {
        return $this->state(fn () => ['status' => 'on_leave']);
    }
}
