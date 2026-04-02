<?php

namespace Tests\Feature\Projects;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Project;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    // ── Projects CRUD ─────────────────────────────────────

    public function test_admin_can_list_projects(): void
    {
        $this->actingAsRole('admin');

        $response = $this->getJson('/api/v1/projects');

        $this->assertSuccessResponse($response);
    }

    public function test_admin_can_create_project(): void
    {
        $this->actingAsRole('admin');
        $dept = Department::factory()->create();
        $manager = Employee::factory()->create(['department_id' => $dept->id]);

        $response = $this->postJson('/api/v1/projects', [
            'name' => 'Community Health Initiative',
            'department_id' => $dept->id,
            'project_manager_id' => $manager->id,
            'start_date' => '2024-06-01',
            'end_date' => '2025-06-01',
            'location' => 'Abuja',
            'status' => 'active',
            'description' => 'A community health project.',
        ]);

        $this->assertSuccessResponse($response, 201);
        $this->assertDatabaseHas('projects', ['name' => 'Community Health Initiative']);
    }

    public function test_admin_can_update_project(): void
    {
        $this->actingAsRole('admin');
        $project = Project::factory()->create();

        $response = $this->patchJson("/api/v1/projects/{$project->id}", [
            'name' => 'Updated Project Name',
        ]);

        $this->assertSuccessResponse($response);
    }

    public function test_staff_cannot_create_project(): void
    {
        $this->actingAsRole('staff');

        $response = $this->postJson('/api/v1/projects', [
            'name' => 'Blocked Project',
            'department_id' => Department::factory()->create()->id,
            'start_date' => '2024-06-01',
        ]);

        $response->assertStatus(403);
    }

    public function test_project_stats_endpoint_works(): void
    {
        $this->actingAsRole('admin');
        Project::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/projects/stats');

        $this->assertSuccessResponse($response);
    }

    // ── Team Members ──────────────────────────────────────

    public function test_admin_can_add_team_member(): void
    {
        $this->actingAsRole('admin');
        $project = Project::factory()->create();
        $employee = Employee::factory()->create();

        $response = $this->postJson("/api/v1/projects/{$project->id}/team", [
            'employee_id' => $employee->id,
            'role' => 'Field Coordinator',
        ]);

        $this->assertSuccessResponse($response, 201);
    }
}
