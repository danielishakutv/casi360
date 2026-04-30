<?php

namespace Tests\Feature\Procurement;

use App\Models\AuditLog;
use App\Models\Boq;
use App\Models\Department;
use App\Models\Grn;
use App\Models\PurchaseOrder;
use App\Models\Requisition;
use App\Models\Rfp;
use App\Models\Rfq;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Locks in the personal-scope filter on the six document list endpoints
 * (PR, PO, BOQ, RFQ, RFP, GRN).
 *
 * Rules under test for every endpoint:
 *   - Super admin and holders of the matching procurement.<feature>.view_all
 *     permission see every record by default.
 *   - Without the permission, the response is silently scoped so the user
 *     only sees records that concern them. The mine flag from the client
 *     cannot widen visibility past that.
 *   - The "concerns me" predicate matches the audit_logs table for every
 *     document type, plus per-document extras (department-mate for PR/PO/
 *     BOQ/RFP, requested_by/submitted_by for PR/PO).
 */
class DocumentListScopeTest extends TestCase
{
    /* ─────────── Requisitions (PR) ─────────── */

    public function test_pr_list_super_admin_sees_all(): void
    {
        $this->actingAsSuperAdmin();
        $this->seedRequisitionsBy(3);

        $response = $this->getJson('/api/v1/procurement/requisitions');

        $this->assertSuccessResponse($response);
        $this->assertCount(3, $response->json('data.requisitions'));
    }

    public function test_pr_list_staff_only_sees_own_or_dept_mate(): void
    {
        $staff = $this->actingAsRole('staff', ['department' => 'IT']);
        $this->seedRequisitionsBy(3); // unrelated departments

        $own = $this->makeRequisition($staff);

        $response = $this->getJson('/api/v1/procurement/requisitions');

        $ids = collect($response->json('data.requisitions'))->pluck('id')->all();
        $this->assertContains($own->id, $ids);
        $this->assertCount(1, $ids);
    }

    public function test_pr_list_admin_can_opt_into_mine_view(): void
    {
        $admin = $this->actingAsRole('admin', ['department' => 'IT']);
        $this->seedRequisitionsBy(3);
        $own = $this->makeRequisition($admin);

        $response = $this->getJson('/api/v1/procurement/requisitions?mine=1');

        $ids = collect($response->json('data.requisitions'))->pluck('id')->all();
        $this->assertEquals([$own->id], $ids);
    }

    public function test_pr_list_staff_cannot_widen_with_mine_zero(): void
    {
        $this->actingAsRole('staff', ['department' => 'Programs']);
        $this->seedRequisitionsBy(3);

        $response = $this->getJson('/api/v1/procurement/requisitions?mine=0');

        $this->assertCount(0, $response->json('data.requisitions'));
    }

    /* ─────────── Purchase Orders (PO) ─────────── */

    public function test_po_list_staff_sees_only_pos_they_touched_in_audit_log(): void
    {
        $staff = $this->actingAsRole('staff', ['department' => 'IT']);

        $touched = $this->makePurchaseOrder();
        $this->makePurchaseOrder(); // unrelated

        // Stamp an audit_logs entry for the PO under the current user
        AuditLog::record($staff->id, 'purchase_order_updated', 'purchase_order', $touched->id, [], []);

        $response = $this->getJson('/api/v1/procurement/purchase-orders');

        $ids = collect($response->json('data.purchase_orders') ?? $response->json('data.items') ?? [])->pluck('id')->all();
        $this->assertContains($touched->id, $ids);
        $this->assertCount(1, $ids);
    }

    public function test_po_list_super_admin_sees_all(): void
    {
        $this->actingAsSuperAdmin();
        $this->makePurchaseOrder();
        $this->makePurchaseOrder();

        $response = $this->getJson('/api/v1/procurement/purchase-orders');

        $this->assertSuccessResponse($response);
        $items = $response->json('data.purchase_orders') ?? $response->json('data.items') ?? [];
        $this->assertCount(2, $items);
    }

    /* ─────────── BOQs ─────────── */

    public function test_boq_list_staff_sees_only_audit_log_touch(): void
    {
        $staff = $this->actingAsRole('staff', ['department' => 'IT']);

        $touched = $this->makeBoq(['department' => 'Programs']);
        $this->makeBoq(['department' => 'Programs']); // unrelated

        AuditLog::record($staff->id, 'boq_updated', 'boq', $touched->id, [], []);

        $response = $this->getJson('/api/v1/procurement/boq');

        $ids = collect($response->json('data.boqs'))->pluck('id')->all();
        $this->assertContains($touched->id, $ids);
        $this->assertCount(1, $ids);
    }

    public function test_boq_list_staff_sees_dept_mate_boqs(): void
    {
        $this->actingAsRole('staff', ['department' => 'Procurement']);

        $sameDept = $this->makeBoq(['department' => 'Procurement']);
        $otherDept = $this->makeBoq(['department' => 'Programs']);

        $response = $this->getJson('/api/v1/procurement/boq');

        $ids = collect($response->json('data.boqs'))->pluck('id')->all();
        $this->assertContains($sameDept->id, $ids);
        $this->assertNotContains($otherDept->id, $ids);
    }

    /* ─────────── RFQ (audit-log only) ─────────── */

    public function test_rfq_list_staff_sees_only_their_touch(): void
    {
        $staff = $this->actingAsRole('staff');

        $touched = $this->makeRfq();
        $this->makeRfq();

        AuditLog::record($staff->id, 'rfq_created', 'rfq', $touched->id, [], []);

        $response = $this->getJson('/api/v1/procurement/rfq');

        $ids = collect($response->json('data.rfqs') ?? $response->json('data.items') ?? [])->pluck('id')->all();
        $this->assertContains($touched->id, $ids);
        $this->assertCount(1, $ids);
    }

    /* ─────────── RFP ─────────── */

    public function test_rfp_list_staff_sees_dept_match_or_audit_touch(): void
    {
        $this->actingAsRole('staff', ['department' => 'Finance']);

        $sameDept = $this->makeRfp(['department' => 'Finance']);
        $otherDept = $this->makeRfp(['department' => 'Programs']);

        $response = $this->getJson('/api/v1/procurement/rfp');

        $ids = collect($response->json('data.rfps') ?? $response->json('data.items') ?? [])->pluck('id')->all();
        $this->assertContains($sameDept->id, $ids);
        $this->assertNotContains($otherDept->id, $ids);
    }

    /* ─────────── GRN (audit-log only) ─────────── */

    public function test_grn_list_staff_sees_only_their_touch(): void
    {
        $staff = $this->actingAsRole('staff');

        $touched = $this->makeGrn();
        $this->makeGrn();

        AuditLog::record($staff->id, 'grn_created', 'grn', $touched->id, [], []);

        $response = $this->getJson('/api/v1/procurement/grn');

        $ids = collect($response->json('data.grns') ?? $response->json('data.items') ?? [])->pluck('id')->all();
        $this->assertContains($touched->id, $ids);
        $this->assertCount(1, $ids);
    }

    /* ----------------------------------------------------------------
     * Helpers
     * ---------------------------------------------------------------- */

    private function seedRequisitionsBy(int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $requester = User::factory()->staff()->create([
                'department' => 'OtherDept' . Str::random(4),
            ]);
            $this->makeRequisition($requester);
        }
    }

    private function makeRequisition(User $requester, array $overrides = []): Requisition
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
            'status'             => 'submitted',
        ], $overrides));
    }

    private function makePurchaseOrder(array $overrides = []): PurchaseOrder
    {
        $department = Department::factory()->create();
        $vendor = Vendor::factory()->create();
        $submitter = User::factory()->staff()->create();
        return PurchaseOrder::create(array_merge([
            'id'                 => (string) Str::uuid(),
            'po_number'          => 'PO-' . strtoupper(Str::random(8)),
            'vendor_id'          => $vendor->id,
            'department_id'      => $department->id,
            'submitted_by'       => $submitter->id,
            'order_date'         => now()->toDateString(),
            'subtotal'           => 1000,
            'total_amount'       => 1000,
            'currency'           => 'NGN',
            'status'             => 'draft',
            'payment_status'     => 'unpaid',
        ], $overrides));
    }

    private function makeBoq(array $overrides = []): Boq
    {
        return Boq::create(array_merge([
            'id'           => (string) Str::uuid(),
            'boq_number'   => 'BOQ-' . strtoupper(Str::random(8)),
            'title'        => 'Test BOQ ' . Str::random(4),
            'department'   => 'Programs',
            'prepared_by'  => 'Tester',
            'status'       => 'draft',
        ], $overrides));
    }

    private function makeRfq(array $overrides = []): Rfq
    {
        return Rfq::create(array_merge([
            'id'         => (string) Str::uuid(),
            'rfq_number' => 'RFQ-' . strtoupper(Str::random(8)),
            'title'      => 'Test RFQ ' . Str::random(4),
            'status'     => 'draft',
        ], $overrides));
    }

    private function makeRfp(array $overrides = []): Rfp
    {
        $vendor = Vendor::factory()->create();
        return Rfp::create(array_merge([
            'id'             => (string) Str::uuid(),
            'rfp_number'     => 'RFP-' . strtoupper(Str::random(8)),
            'vendor_id'      => $vendor->id,
            'payee'          => 'Test Payee',
            'currency'       => 'NGN',
            'date'           => now()->toDateString(),
            'subtotal'       => 1000,
            'total_amount'   => 1000,
            'status'         => 'draft',
        ], $overrides));
    }

    private function makeGrn(array $overrides = []): Grn
    {
        $vendor = Vendor::factory()->create();
        return Grn::create(array_merge([
            'id'            => (string) Str::uuid(),
            'grn_number'    => 'GRN-' . strtoupper(Str::random(8)),
            'vendor_id'     => $vendor->id,
            'status'        => 'draft',
            'received_date' => now()->toDateString(),
        ], $overrides));
    }
}
