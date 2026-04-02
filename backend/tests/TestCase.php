<?php

namespace Tests;

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    /**
     * Run permission seeder before each test so role-based access works.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
    }

    /**
     * Create and authenticate as a user with the given role.
     */
    protected function actingAsRole(string $role = 'admin', array $overrides = []): User
    {
        $user = User::factory()->create(array_merge([
            'role' => $role,
            'status' => 'active',
            'force_password_change' => false,
        ], $overrides));

        $this->actingAs($user);

        return $user;
    }

    /**
     * Create a super admin and authenticate.
     */
    protected function actingAsSuperAdmin(array $overrides = []): User
    {
        return $this->actingAsRole('super_admin', $overrides);
    }

    /**
     * Assert a standard success JSON structure.
     */
    protected function assertSuccessResponse($response, int $status = 200): void
    {
        $response->assertStatus($status)
            ->assertJsonStructure(['success', 'message', 'data'])
            ->assertJson(['success' => true]);
    }

    /**
     * Assert a standard error JSON structure.
     */
    protected function assertErrorResponse($response, int $status = 400): void
    {
        $response->assertStatus($status)
            ->assertJson(['success' => false]);
    }

    /**
     * Assert a standard paginated response.
     */
    protected function assertPaginatedResponse($response, string $dataKey): void
    {
        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'data' => [
                    $dataKey,
                    'meta' => ['current_page', 'last_page', 'per_page', 'total'],
                ],
            ]);
    }
}
