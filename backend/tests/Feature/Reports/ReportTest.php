<?php

namespace Tests\Feature\Reports;

use Tests\TestCase;

class ReportTest extends TestCase
{
    // ── HR Reports ────────────────────────────────────────

    public function test_admin_can_view_employee_report(): void
    {
        $this->actingAsRole('admin');

        $response = $this->getJson('/api/v1/reports/hr/employees');

        $this->assertSuccessResponse($response);
    }

    public function test_admin_can_view_department_report(): void
    {
        $this->actingAsRole('admin');

        $response = $this->getJson('/api/v1/reports/hr/departments');

        $this->assertSuccessResponse($response);
    }

    // ── Procurement Reports ───────────────────────────────

    public function test_admin_can_view_purchase_orders_report(): void
    {
        $this->actingAsRole('admin');

        $response = $this->getJson('/api/v1/reports/procurement/purchase-orders');

        $this->assertSuccessResponse($response);
    }

    public function test_admin_can_view_vendor_report(): void
    {
        $this->actingAsRole('admin');

        $response = $this->getJson('/api/v1/reports/procurement/vendors');

        $this->assertSuccessResponse($response);
    }

    // ── Project Reports ───────────────────────────────────

    public function test_admin_can_view_project_summary_report(): void
    {
        $this->actingAsRole('admin');

        $response = $this->getJson('/api/v1/reports/projects/summary');

        $this->assertSuccessResponse($response);
    }

    // ── Audit Reports ─────────────────────────────────────

    public function test_staff_cannot_view_audit_logs(): void
    {
        $this->actingAsRole('staff');

        $response = $this->getJson('/api/v1/reports/audit/logs');

        $response->assertStatus(403);
    }

    public function test_admin_can_view_audit_logs(): void
    {
        $this->actingAsRole('admin');

        $response = $this->getJson('/api/v1/reports/audit/logs');

        $this->assertSuccessResponse($response);
    }

    // ── Finance Reports ───────────────────────────────────

    public function test_admin_can_view_finance_overview(): void
    {
        $this->actingAsRole('admin');

        $response = $this->getJson('/api/v1/reports/finance/overview');

        $this->assertSuccessResponse($response);
    }
}
