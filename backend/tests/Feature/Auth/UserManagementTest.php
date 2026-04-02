<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    public function test_admin_can_register_new_user(): void
    {
        $this->actingAsRole('admin');

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'New User',
            'email' => 'newuser@casi360.test',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'role' => 'staff',
            'department' => 'HR',
        ]);

        $this->assertSuccessResponse($response, 201);
        $this->assertDatabaseHas('users', ['email' => 'newuser@casi360.test', 'role' => 'staff']);
    }

    public function test_staff_cannot_register_users(): void
    {
        $this->actingAsRole('staff');

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'New User',
            'email' => 'blocked@casi360.test',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_list_users(): void
    {
        $this->actingAsRole('admin');
        User::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/auth/users');

        $this->assertSuccessResponse($response);
        $response->assertJsonStructure(['data' => ['users', 'meta']]);
    }

    public function test_admin_can_update_user_role(): void
    {
        $admin = $this->actingAsRole('admin');
        $staff = User::factory()->staff()->create();

        $response = $this->patchJson("/api/v1/auth/users/{$staff->id}/role", [
            'role' => 'manager',
        ]);

        $this->assertSuccessResponse($response);
        $this->assertDatabaseHas('users', ['id' => $staff->id, 'role' => 'manager']);
    }

    public function test_admin_cannot_change_own_role(): void
    {
        $admin = $this->actingAsRole('admin');

        $response = $this->patchJson("/api/v1/auth/users/{$admin->id}/role", [
            'role' => 'staff',
        ]);

        $this->assertErrorResponse($response, 403);
    }

    public function test_admin_can_deactivate_user(): void
    {
        $this->actingAsRole('admin');
        $user = User::factory()->create();

        $response = $this->deleteJson("/api/v1/auth/users/{$user->id}");

        $this->assertSuccessResponse($response);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'status' => 'inactive']);
    }

    public function test_admin_can_update_user_status(): void
    {
        $this->actingAsRole('admin');
        $user = User::factory()->create(['status' => 'active']);

        $response = $this->patchJson("/api/v1/auth/users/{$user->id}/status", [
            'status' => 'inactive',
        ]);

        $this->assertSuccessResponse($response);
    }

    public function test_user_can_change_password(): void
    {
        $user = $this->actingAsRole('staff');

        $response = $this->postJson('/api/v1/auth/change-password', [
            'current_password' => 'Password123!',
            'password' => 'NewPassword456!',
            'password_confirmation' => 'NewPassword456!',
        ]);

        $this->assertSuccessResponse($response);
    }

    public function test_user_can_view_and_update_profile(): void
    {
        $user = $this->actingAsRole('staff');

        $response = $this->getJson('/api/v1/auth/profile');
        $this->assertSuccessResponse($response);

        $response = $this->patchJson('/api/v1/auth/profile', [
            'phone' => '+2348012345678',
        ]);
        $this->assertSuccessResponse($response);
    }

    public function test_force_password_change_blocks_protected_routes(): void
    {
        $user = $this->actingAsRole('staff', ['force_password_change' => true]);

        // Session should still work
        $response = $this->getJson('/api/v1/auth/session');
        $this->assertSuccessResponse($response);

        // Profile should be blocked
        $response = $this->getJson('/api/v1/auth/profile');
        $response->assertStatus(403);
    }
}
