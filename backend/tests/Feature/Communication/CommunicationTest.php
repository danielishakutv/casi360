<?php

namespace Tests\Feature\Communication;

use App\Models\Department;
use App\Models\Forum;
use App\Models\User;
use Tests\TestCase;

class CommunicationTest extends TestCase
{
    // ── Notices ───────────────────────────────────────────

    public function test_admin_can_list_notices(): void
    {
        $this->actingAsRole('admin');

        $response = $this->getJson('/api/v1/communication/notices');

        $this->assertSuccessResponse($response);
    }

    public function test_admin_can_create_notice(): void
    {
        $this->actingAsRole('admin');

        $response = $this->postJson('/api/v1/communication/notices', [
            'title' => 'Staff Meeting Tomorrow',
            'body' => 'All staff are required to attend the meeting at 10am.',
            'priority' => 'important',
            'status' => 'published',
            'audiences' => [
                ['audience_type' => 'all'],
            ],
        ]);

        $this->assertSuccessResponse($response, 201);
        $this->assertDatabaseHas('notices', ['title' => 'Staff Meeting Tomorrow']);
    }

    public function test_staff_cannot_create_notice(): void
    {
        $this->actingAsRole('staff');

        $response = $this->postJson('/api/v1/communication/notices', [
            'title' => 'Blocked Notice',
            'body' => 'This should fail.',
            'audiences' => [['audience_type' => 'all']],
        ]);

        $response->assertStatus(403);
    }

    public function test_notice_stats_endpoint_works(): void
    {
        $this->actingAsRole('admin');

        $response = $this->getJson('/api/v1/communication/notices/stats');

        $this->assertSuccessResponse($response);
    }

    // ── Forums ────────────────────────────────────────────

    public function test_admin_can_list_forums(): void
    {
        $this->actingAsRole('admin');
        Forum::factory()->count(2)->create();

        $response = $this->getJson('/api/v1/communication/forums');

        $this->assertSuccessResponse($response);
    }

    public function test_admin_can_create_forum(): void
    {
        $this->actingAsRole('admin');

        $response = $this->postJson('/api/v1/communication/forums', [
            'name' => 'General Discussion',
            'description' => 'Open forum for all staff.',
            'type' => 'general',
            'status' => 'active',
        ]);

        $this->assertSuccessResponse($response, 201);
        $this->assertDatabaseHas('forums', ['name' => 'General Discussion']);
    }

    // ── Messages ──────────────────────────────────────────

    public function test_user_can_send_message(): void
    {
        $sender = $this->actingAsRole('admin');
        $recipient = User::factory()->create();

        $response = $this->postJson('/api/v1/communication/messages', [
            'recipient_id' => $recipient->id,
            'subject' => 'Hello',
            'body' => 'This is a test message.',
        ]);

        $this->assertSuccessResponse($response, 201);
    }

    public function test_user_can_list_messages(): void
    {
        $this->actingAsRole('admin');

        $response = $this->getJson('/api/v1/communication/messages');

        $this->assertSuccessResponse($response);
    }
}
