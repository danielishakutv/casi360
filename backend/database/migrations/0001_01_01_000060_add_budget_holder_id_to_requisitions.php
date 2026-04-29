<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Add explicit budget_holder_id to requisitions.
 *
 * Until now the budget-holder approver has been derived dynamically from the
 * linked project's project manager. This migration makes the budget holder a
 * first-class field on the requisition so the requester can override it at
 * creation time (any active employee).
 *
 * Column points at employees.id to match the existing project_manager_id
 * pattern (Project::projectManager() returns an Employee). The
 * ApprovalAuthorizer continues to bridge to the logged-in User by email.
 *
 * Existing rows are backfilled from project.project_manager_id so in-flight
 * PRs continue to route to the same person they would have under the old
 * dynamic logic.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requisitions', function (Blueprint $table) {
            $table->uuid('budget_holder_id')->nullable()->after('project_id');
            $table->foreign('budget_holder_id')
                ->references('id')->on('employees')
                ->nullOnDelete();
            $table->index('budget_holder_id');
        });

        // Backfill: copy the linked project's manager into budget_holder_id
        // for every existing requisition that has a project but no explicit
        // budget holder. Non-destructive — only fills nulls.
        DB::statement("
            UPDATE requisitions r
            INNER JOIN projects p ON p.id = r.project_id
            SET r.budget_holder_id = p.project_manager_id
            WHERE r.budget_holder_id IS NULL
              AND p.project_manager_id IS NOT NULL
        ");
    }

    public function down(): void
    {
        Schema::table('requisitions', function (Blueprint $table) {
            $table->dropForeign(['budget_holder_id']);
            $table->dropIndex(['budget_holder_id']);
            $table->dropColumn('budget_holder_id');
        });
    }
};
