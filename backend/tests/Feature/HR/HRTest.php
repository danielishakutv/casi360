<?php

namespace Tests\Feature\HR;

use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use Tests\TestCase;

class HRTest extends TestCase
{
    // ── Departments ───────────────────────────────────────

    public function test_admin_can_list_departments(): void
    {
        $this->actingAsRole('admin');
        Department::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/hr/departments');

        $this->assertSuccessResponse($response);
    }

    public function test_admin_can_create_department(): void
    {
        $this->actingAsRole('admin');

        $response = $this->postJson('/api/v1/hr/departments', [
            'name' => 'Engineering',
            'head' => 'Jane Doe',
            'description' => 'Software engineering team',
            'status' => 'active',
        ]);

        $this->assertSuccessResponse($response, 201);
        $this->assertDatabaseHas('departments', ['name' => 'Engineering']);
    }

    public function test_admin_can_update_department(): void
    {
        $this->actingAsRole('admin');
        $dept = Department::factory()->create();

        $response = $this->patchJson("/api/v1/hr/departments/{$dept->id}", [
            'name' => 'Updated Engineering',
        ]);

        $this->assertSuccessResponse($response);
    }

    public function test_staff_cannot_create_department(): void
    {
        $this->actingAsRole('staff');

        $response = $this->postJson('/api/v1/hr/departments', [
            'name' => 'Blocked',
        ]);

        $response->assertStatus(403);
    }

    // ── Employees ─────────────────────────────────────────

    public function test_admin_can_list_employees(): void
    {
        $this->actingAsRole('admin');

        $response = $this->getJson('/api/v1/hr/employees');

        $this->assertSuccessResponse($response);
    }

    public function test_admin_can_create_employee(): void
    {
        $this->actingAsRole('admin');
        $dept = Department::factory()->create();
        $desig = Designation::factory()->create(['department_id' => $dept->id]);

        $response = $this->postJson('/api/v1/hr/employees', [
            'name' => 'John Doe',
            'email' => 'jdoe@casi360.test',
            'phone' => '+234801234567',
            'department_id' => $dept->id,
            'designation_id' => $desig->id,
            'gender' => 'male',
            'date_of_birth' => '1990-05-15',
            'join_date' => '2024-01-15',
            'status' => 'active',
        ]);

        $this->assertSuccessResponse($response, 201);
    }

    public function test_employee_stats_endpoint_works(): void
    {
        $this->actingAsRole('admin');

        $response = $this->getJson('/api/v1/hr/employees/stats');

        $this->assertSuccessResponse($response);
    }

    // ── Designations ──────────────────────────────────────

    public function test_admin_can_list_designations(): void
    {
        $this->actingAsRole('admin');

        $response = $this->getJson('/api/v1/hr/designations');

        $this->assertSuccessResponse($response);
    }
}
