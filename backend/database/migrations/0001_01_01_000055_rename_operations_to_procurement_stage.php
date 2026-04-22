<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Temporarily widen the enum so both 'operations' and 'procurement' are valid,
        // then remap existing rows, then narrow back to the new set.
        DB::statement("ALTER TABLE requisition_approvals MODIFY COLUMN stage ENUM('budget_holder','finance','operations','procurement') NOT NULL");

        DB::statement("UPDATE requisition_approvals SET stage = 'procurement' WHERE stage = 'operations'");
        DB::statement("UPDATE requisition_approvals SET stage_label = 'Procurement' WHERE stage = 'procurement' AND (stage_label = 'Operations' OR stage_label IS NULL)");

        DB::statement("ALTER TABLE requisition_approvals MODIFY COLUMN stage ENUM('budget_holder','finance','procurement') NOT NULL");

        // Update the audit log for historical consistency
        DB::statement("UPDATE requisition_audit_logs SET stage = 'procurement' WHERE stage = 'operations'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE requisition_approvals MODIFY COLUMN stage ENUM('budget_holder','finance','operations','procurement') NOT NULL");

        DB::statement("UPDATE requisition_approvals SET stage = 'operations' WHERE stage = 'procurement'");
        DB::statement("UPDATE requisition_approvals SET stage_label = 'Operations' WHERE stage = 'operations' AND stage_label = 'Procurement'");

        DB::statement("ALTER TABLE requisition_approvals MODIFY COLUMN stage ENUM('budget_holder','finance','operations') NOT NULL");

        DB::statement("UPDATE requisition_audit_logs SET stage = 'operations' WHERE stage = 'procurement'");
    }
};
