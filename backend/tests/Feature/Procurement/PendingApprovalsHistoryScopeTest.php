<?php

namespace Tests\Feature\Procurement;

use App\Models\Department;
use App\Models\Requisition;
use App\Models\RequisitionAuditLog;
use App\Models\User;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Covers the personal-scope filter on
 * GET /api/v1/procurement/pending-approvals?scope=history
 *
 * Rules under test:
 *   - super_admin and holders of procurement.approvals.view_all see every
 *     completed PR. Without the permission, the response is silently scoped
 *     to records that concern the caller.
 *   - "concerns me" = requested by, submitted by, audit-log entry by, or
 *     same-department-as-requester. The flag from the client cannot widen
 *     visibility past these rules.
 */
class PendingApprovalsHistoryScopeTest extends TestCase
{
    private const ENDPOINT = '/api/v1/procurement/pending-approvals?scope=history';

    public function test_super_admin_sees_all_completed_requisitions(): void
    {
        $this->actingAsSuperAdmin();
        $this->seedThreeUnrelatedRequisitions();

        $response = $this->getJson(self::ENDPOINT);

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data.requisitions'));
    }

    public function test_staff_without_view_all_only_sees_their_own_requisition(): void
    {
        $staff = $this->actingAsRole('staff', ['department' => 'IT']);
        $this->seedThreeUnrelatedRequisitions();
        $own = $this->makeApprovedRequisition($staff, ['title' => 'My Laptop']);

        $response = $this->getJson(self::ENDPOINT);

        $response->assertStatus(200);
        $items = $response->json('data.requisitions');
        $this->assertCount(1, $items);
        $this->assertEquals($own->id, $items[0]['id']);
    }

    public function test_staff_sees_pr_when_a_department_mate_requested_it(): void
    {
        $staff = $this->actingAsRole('staff', ['department' => 'Finance']);

        $colleague = User::factory()->staff()->create(['department' => 'Finance']);
        $strangerDept = User::factory()->staff()->create(['department' => 'HR']);

        $colleaguePr = $this->makeApprovedRequisition($colleague);
        $strangerPr  = $this->makeApprovedRequisition($strangerDept);

        $response = $this->getJson(self::ENDPOINT);

        $response->assertStatus(200);
        $ids = collect($response->json('data.requisitions'))->pluck('id')->all();
        $this->assertContains($colleaguePr->id, $ids);
        $this->assertNotContains($strangerPr->id, $ids);
    }

    public function test_staff_sees_pr_when_they_have_an_audit_log_entry(): void
    {
        $staff = $this->actingAsRole('staff', ['department' => 'IT']);

        // PR created by someone else, no department overlap, but the current
        // user later commented / acted (recorded as an audit-log entry).
        $other = User::factory()->staff()->create(['department' => 'Programs']);
        $touched = $this->makeApprovedRequisition($other);

        RequisitionAuditLog::create([
            'requisition_id' => $touched->id,
            'actor_id'       => $staff->id,
            'actor_name'     => $staff->name,
            'action'         => 'updated',
            'from_status'    => 'submitted',
            'to_status'      => 'submitted',
        ]);

        // Plus an unrelated PR that should not appear
        $unrelated = $this->makeApprovedRequisition($other);

        $response = $this->getJson(self::ENDPOINT);

        $response->assertStatus(200);
        $ids = collect($response->json('data.requisitions'))->pluck('id')->all();
        $this->assertContains($touched->id, $ids);
        $this->assertNotContains($unrelated->id, $ids);
    }

    public function test_admin_with_view_all_sees_everything_by_default(): void
    {
        // Admin role gets procurement.approvals.view_all=true via the seeder.
        $this->actingAsRole('admin', ['department' => 'IT']);
        $this->seedThreeUnrelatedRequisitions();

        $response = $this->getJson(self::ENDPOINT);

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data.requisitions'));
    }

    public function test_admin_can_opt_into_personal_view_via_mine_flag(): void
    {
        $admin = $this->actingAsRole('admin', ['department' => 'IT']);
        $this->seedThreeUnrelatedRequisitions();
        $own = $this->makeApprovedRequisition($admin, ['title' => 'My PR']);

        $response = $this->getJson(self::ENDPOINT . '&mine=1');

        $response->assertStatus(200);
        $items = $response->json('data.requisitions');
        $this->assertCount(1, $items);
        $this->assertEquals($own->id, $items[0]['id']);
    }

    public function test_user_without_permission_cannot_widen_scope_via_mine_flag(): void
    {
        $staff = $this->actingAsRole('staff', ['department' => 'IT']);
        $this->seedThreeUnrelatedRequisitions();

        // Sending mine=0 must NOT escape the personal scope server-side.
        $response = $this->getJson(self::ENDPOINT . '&mine=0');

        $response->assertStatus(200);
        $this->assertCount(0, $response->json('data.requisitions'));
    }

    public function test_user_without_a_department_does_not_see_unrelated_no_department_prs(): void
    {
        // Edge case: a user with NULL/empty department string should not
        // accidentally match every requester whose department is also blank.
        $staff = $this->actingAsRole('staff', ['department' => null]);

        $other = User::factory()->staff()->create(['department' => null]);
        $unrelated = $this->makeApprovedRequisition($other);

        $response = $this->getJson(self::ENDPOINT);

        $response->assertStatus(200);
        $this->assertCount(0, $response->json('data.requisitions'));
    }

    /* ----------------------------------------------------------------
     * Helpers
     * ---------------------------------------------------------------- */

    private function seedThreeUnrelatedRequisitions(): void
    {
        $requesters = [
            User::factory()->staff()->create(['department' => 'Programs']),
            User::factory()->staff()->create(['department' => 'HR']),
            User::factory()->staff()->create(['department' => 'Logistics']),
        ];
        foreach ($requesters as $r) {
            $this->makeApprovedRequisition($r);
        }
    }

    private function makeApprovedRequisition(User $requester, array $overrides = []): Requisition
    {
        $department = Department::factory()->create();

        return Requisition::create(array_merge([
            'id'                 => (string) Str::uuid(),
            'requisition_number' => 'PR-' . strtoupper(Str::random(8)),
            'department_id'      => $department->id,
            'requested_by'       => $requester->id,
            'submitted_by'       => $requester->id,
            'title'              => 'Test PR ' . Str::random(4),
            'priority'           => 'medium',
            'estimated_cost'     => 1000,
            'status'             => 'approved',
        ], $overrides));
    }
}
