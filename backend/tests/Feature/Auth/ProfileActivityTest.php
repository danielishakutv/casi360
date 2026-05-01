<?php

namespace Tests\Feature\Auth;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Requisition;
use App\Models\User;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Locks in the unified profile activity feed:
 * GET /api/v1/auth/activity returns recent audit-log entries the
 * current user authored, joined to each procurement document for
 * display details (number, title, status). Records the user did not
 * touch must not appear.
 */
class ProfileActivityTest extends TestCase
{
    public function test_activity_returns_only_logs_authored_by_the_user(): void
    {
        $user = $this->actingAsRole('staff');
        $other = User::factory()->staff()->create();

        $myReq    = $this->makeRequisition($user);
        $theirReq = $this->makeRequisition($other);

        AuditLog::record($user->id,  'requisition_created', 'requisition', $myReq->id, [], []);
        AuditLog::record($other->id, 'requisition_created', 'requisition', $theirReq->id, [], []);

        $response = $this->getJson('/api/v1/auth/activity');

        $response->assertStatus(200);
        $items = $response->json('data.activity');
        $this->assertCount(1, $items);
        $this->assertEquals('requisition', $items[0]['entity_type']);
        $this->assertEquals($myReq->id, $items[0]['entity_id']);
        $this->assertEquals($myReq->requisition_number, $items[0]['number']);
        $this->assertEquals($myReq->title, $items[0]['title']);
        $this->assertEquals($myReq->status, $items[0]['status']);
    }

    public function test_activity_orders_newest_first(): void
    {
        $user = $this->actingAsRole('staff');
        $reqA = $this->makeRequisition($user);
        $reqB = $this->makeRequisition($user);

        $older = AuditLog::record($user->id, 'requisition_created', 'requisition', $reqA->id, [], []);
        $newer = AuditLog::record($user->id, 'requisition_updated', 'requisition', $reqB->id, [], []);

        // Force a deterministic ordering — model uses now() at insert time so
        // we shift the older row back one minute to avoid a tie.
        AuditLog::where('id', $older->id)->update(['created_at' => now()->subMinute()]);

        $response = $this->getJson('/api/v1/auth/activity');

        $items = $response->json('data.activity');
        $this->assertCount(2, $items);
        $this->assertEquals($reqB->id, $items[0]['entity_id']);
        $this->assertEquals($reqA->id, $items[1]['entity_id']);
    }

    public function test_activity_marks_deleted_entities_gracefully(): void
    {
        $user = $this->actingAsRole('staff');
        $req = $this->makeRequisition($user);

        AuditLog::record($user->id, 'requisition_created', 'requisition', $req->id, [], []);

        // Hard-delete the requisition (still possible via raw DB) — the audit
        // log row remains but the entity is gone.
        $req->delete();

        $response = $this->getJson('/api/v1/auth/activity');

        $items = $response->json('data.activity');
        $this->assertCount(1, $items);
        $this->assertTrue($items[0]['deleted']);
        $this->assertNull($items[0]['number']);
    }

    public function test_activity_respects_per_page_limit(): void
    {
        $user = $this->actingAsRole('staff');

        for ($i = 0; $i < 4; $i++) {
            $req = $this->makeRequisition($user);
            AuditLog::record($user->id, 'requisition_updated', 'requisition', $req->id, [], []);
        }

        $response = $this->getJson('/api/v1/auth/activity?per_page=2');

        $this->assertCount(2, $response->json('data.activity'));
        $this->assertEquals(2, $response->json('data.meta.per_page'));
        $this->assertEquals(4, $response->json('data.meta.total'));
    }

    public function test_activity_ignores_unrelated_audit_log_entries(): void
    {
        $user = $this->actingAsRole('staff');
        $req = $this->makeRequisition($user);

        // A non-document audit-log entry for the same user must not leak in
        AuditLog::record($user->id, 'profile_updated', 'user', $user->id, [], []);
        AuditLog::record($user->id, 'requisition_created', 'requisition', $req->id, [], []);

        $response = $this->getJson('/api/v1/auth/activity');

        $items = $response->json('data.activity');
        $this->assertCount(1, $items);
        $this->assertEquals('requisition', $items[0]['entity_type']);
    }

    /* ----------------------------------------------------------------
     * Helpers
     * ---------------------------------------------------------------- */

    private function makeRequisition(User $requester): Requisition
    {
        $department = Department::factory()->create();
        return Requisition::create([
            'id'                 => (string) Str::uuid(),
            'requisition_number' => 'PR-' . strtoupper(Str::random(8)),
            'department_id'      => $department->id,
            'requested_by'       => $requester->id,
            'submitted_by'       => $requester->id,
            'title'              => 'Test PR ' . Str::random(4),
            'priority'           => 'medium',
            'estimated_cost'     => 1000,
            'status'             => 'submitted',
        ]);
    }
}
