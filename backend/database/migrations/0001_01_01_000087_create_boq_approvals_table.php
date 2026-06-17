<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ED process §1 — a BoQ follows the SAME 4-stage pre-procurement chain as a PR:
 * Budget Holder → Finance → Procurement → Operations (Final).
 *
 * Adds boq_approvals (mirrors requisition_approvals), a budget_holder_id +
 * created_by on boqs (for the chain, originator-skip, and segregation of
 * duties), and widens boqs.status to carry pending_approval. Additive +
 * idempotent.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('boq_approvals')) {
            Schema::create('boq_approvals', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('boq_id')->constrained('boqs')->cascadeOnDelete();
                $table->enum('stage', ['budget_holder', 'finance', 'procurement', 'operations']);
                $table->tinyInteger('stage_order')->unsigned();
                $table->string('stage_label', 50);
                $table->enum('status', ['waiting', 'pending', 'approved', 'rejected', 'revision', 'skipped'])->default('waiting');
                $table->foreignUuid('actor_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('actor_name')->nullable();
                $table->string('actor_position')->nullable();
                $table->text('comments')->nullable();
                $table->timestamp('decided_at')->nullable();
                $table->timestamps();

                $table->index('boq_id');
                $table->index('status');
                $table->index(['boq_id', 'stage'], 'boq_approvals_boq_stage_idx');
                $table->index(['boq_id', 'status'], 'boq_approvals_boq_status_idx');
            });
        }

        Schema::table('boqs', function (Blueprint $table) {
            if (!Schema::hasColumn('boqs', 'budget_holder_id')) {
                $table->foreignUuid('budget_holder_id')->nullable()->after('prepared_by')
                    ->constrained('employees')->nullOnDelete();
            }
            if (!Schema::hasColumn('boqs', 'created_by')) {
                $table->foreignUuid('created_by')->nullable()->after('budget_holder_id')
                    ->constrained('users')->nullOnDelete();
            }
        });

        DB::statement(
            "ALTER TABLE boqs MODIFY COLUMN status "
            . "ENUM('draft','submitted','pending_approval','approved','revised','rejected') NOT NULL DEFAULT 'draft'"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('boq_approvals');

        Schema::table('boqs', function (Blueprint $table) {
            if (Schema::hasColumn('boqs', 'created_by')) {
                $table->dropConstrainedForeignId('created_by');
            }
            if (Schema::hasColumn('boqs', 'budget_holder_id')) {
                $table->dropConstrainedForeignId('budget_holder_id');
            }
        });

        DB::statement("UPDATE boqs SET status = 'submitted' WHERE status = 'pending_approval'");
        DB::statement(
            "ALTER TABLE boqs MODIFY COLUMN status "
            . "ENUM('draft','submitted','approved','revised','rejected') NOT NULL DEFAULT 'draft'"
        );
    }
};
