<?php

namespace Tests\Feature\Settings;

use Tests\TestCase;

class SettingsTest extends TestCase
{
    // ── Permissions ───────────────────────────────────────

    public function test_super_admin_can_view_permissions_matrix(): void
    {
        $this->actingAsSuperAdmin();

        $response = $this->getJson('/api/v1/settings/permissions');

        $this->assertSuccessResponse($response);
    }

    public function test_admin_cannot_view_permissions_matrix(): void
    {
        $this->actingAsRole('admin');

        $response = $this->getJson('/api/v1/settings/permissions');

        $response->assertStatus(403);
    }

    public function test_user_can_view_own_permissions(): void
    {
        $this->actingAsRole('staff');

        $response = $this->getJson('/api/v1/auth/permissions');

        $this->assertSuccessResponse($response);
    }

    // ── System Settings ───────────────────────────────────

    public function test_super_admin_can_view_settings(): void
    {
        $this->actingAsSuperAdmin();

        $response = $this->getJson('/api/v1/settings/general');

        $this->assertSuccessResponse($response);
    }

    public function test_admin_cannot_view_settings(): void
    {
        $this->actingAsRole('admin');

        $response = $this->getJson('/api/v1/settings/general');

        $response->assertStatus(403);
    }

    public function test_public_settings_accessible_without_auth(): void
    {
        $response = $this->getJson('/api/v1/settings/general/public');

        $this->assertSuccessResponse($response);
    }
}
