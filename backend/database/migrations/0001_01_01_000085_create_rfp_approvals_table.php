<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * v2 §3.3 — Post-procurement Payment Request (RFP) approval chain.
 *
 * Adds the rfp_approvals table (Programme Manager → Finance → Final Approver)
 * and widens the rfps.status enum to carry the approval lifecycle
 * (pending_approval / revision / on_hold). Mirrors the requisition_approvals
 * design. Additive + idempotent.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('rfp_approvals')) {
            Schema::create('rfp_approvals', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('rfp_id')->constrained('rfps')->cascadeOnDelete();
                $table->enum('stage', ['programme_manager', 'finance', 'final_approver']);
                $table->tinyInteger('stage_order')->unsigned();
                $table->string('stage_label', 50);
                $table->enum('status', ['waiting', 'pending', 'approved', 'rejected', 'revision', 'skipped'])->default('waiting');
                $table->foreignUuid('actor_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('actor_name')->nullable();
                $table->string('actor_position')->nullable();
                $table->text('comments')->nullable();
                $table->timestamp('decided_at')->nullable();
                $table->timestamps();

                $table->index('rfp_id');
                $table->index('status');
                $table->index(['rfp_id', 'stage'], 'rfp_approvals_rfp_stage_idx');
                $table->index(['rfp_id', 'status'], 'rfp_approvals_rfp_status_idx');
            });
        }

        // Widen the rfps.status enum to carry the approval lifecycle.
        DB::statement(
            "ALTER TABLE rfps MODIFY COLUMN status "
            . "ENUM('draft','submitted','pending_approval','approved','paid','rejected','revision','on_hold') "
            . "NOT NULL DEFAULT 'draft'"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('rfp_approvals');

        // Re-point lifecycle-only statuses to the nearest legacy value before narrowing.
        DB::statement("UPDATE rfps SET status = 'submitted' WHERE status IN ('pending_approval','revision','on_hold')");
        DB::statement(
            "ALTER TABLE rfps MODIFY COLUMN status "
            . "ENUM('draft','submitted','approved','paid','rejected') NOT NULL DEFAULT 'draft'"
        );
    }
};
