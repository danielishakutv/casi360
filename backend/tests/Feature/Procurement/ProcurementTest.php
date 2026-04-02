<?php

namespace Tests\Feature\Procurement;

use App\Models\Department;
use App\Models\Employee;
use App\Models\PurchaseOrder;
use App\Models\Vendor;
use Tests\TestCase;

class ProcurementTest extends TestCase
{
    // ── Vendors ───────────────────────────────────────────

    public function test_admin_can_list_vendors(): void
    {
        $this->actingAsRole('admin');
        Vendor::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/procurement/vendors');

        $this->assertSuccessResponse($response);
    }

    public function test_admin_can_create_vendor(): void
    {
        $this->actingAsRole('admin');

        $response = $this->postJson('/api/v1/procurement/vendors', [
            'name' => 'Acme Supplies Ltd',
            'contact_person' => 'John Vendor',
            'email' => 'john@acme.test',
            'phone' => '+234801234567',
            'city' => 'Lagos',
            'status' => 'active',
        ]);

        $this->assertSuccessResponse($response, 201);
        $this->assertDatabaseHas('vendors', ['name' => 'Acme Supplies Ltd']);
    }

    public function test_admin_can_update_vendor(): void
    {
        $this->actingAsRole('admin');
        $vendor = Vendor::factory()->create();

        $response = $this->patchJson("/api/v1/procurement/vendors/{$vendor->id}", [
            'name' => 'Updated Vendor Name',
        ]);

        $this->assertSuccessResponse($response);
    }

    public function test_staff_cannot_create_vendor(): void
    {
        $this->actingAsRole('staff');

        $response = $this->postJson('/api/v1/procurement/vendors', [
            'name' => 'Blocked Vendor',
        ]);

        $response->assertStatus(403);
    }

    // ── Purchase Orders ───────────────────────────────────

    public function test_admin_can_list_purchase_orders(): void
    {
        $this->actingAsRole('admin');

        $response = $this->getJson('/api/v1/procurement/purchase-orders');

        $this->assertSuccessResponse($response);
    }

    public function test_admin_can_create_purchase_order(): void
    {
        $this->actingAsRole('admin');
        $vendor = Vendor::factory()->create();
        $dept = Department::factory()->create();
        $employee = Employee::factory()->create(['department_id' => $dept->id]);

        $response = $this->postJson('/api/v1/procurement/purchase-orders', [
            'vendor_id' => $vendor->id,
            'department_id' => $dept->id,
            'requested_by' => $employee->id,
            'order_date' => now()->toDateString(),
            'items' => [
                [
                    'description' => 'Office desk',
                    'quantity' => 5,
                    'unit_price' => 15000,
                ],
            ],
        ]);

        $this->assertSuccessResponse($response, 201);
    }
}
