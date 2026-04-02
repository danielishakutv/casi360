<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Tests\TestCase;

class LoginTest extends TestCase
{
    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create(['password' => 'Password123!']);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'Password123!',
        ]);

        $this->assertSuccessResponse($response);
        $response->assertJsonPath('data.user.id', $user->id);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertErrorResponse($response, 401);
    }

    public function test_login_fails_with_missing_fields(): void
    {
        $response = $this->postJson('/api/v1/auth/login', []);

        $response->assertStatus(422);
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::factory()->inactive()->create(['password' => 'Password123!']);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'Password123!',
        ]);

        $this->assertErrorResponse($response, 403);
    }

    public function test_authenticated_user_can_get_session(): void
    {
        $user = $this->actingAsRole('admin');

        $response = $this->getJson('/api/v1/auth/session');

        $this->assertSuccessResponse($response);
        $response->assertJsonPath('data.user.id', $user->id);
    }

    public function test_authenticated_user_can_logout(): void
    {
        $this->actingAsRole('staff');

        $response = $this->postJson('/api/v1/auth/logout');

        $this->assertSuccessResponse($response);
    }

    public function test_unauthenticated_user_cannot_access_session(): void
    {
        $response = $this->getJson('/api/v1/auth/session');

        $response->assertStatus(401);
    }
}
