<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * v2 §3.2 — Mandatory procurement compliance checklist.
 *
 * Before a Request for Payment (Payment Request) can be raised, the
 * Procurement Unit must affirm EITHER that all procurement procedures were
 * duly followed OR that the process was waived (with a justification and a
 * link/reference to the supporting document). This migration adds the columns
 * that capture that affirmation; the gate itself is enforced in
 * StoreRfpRequest. All columns are nullable so existing RFPs are untouched.
 *
 * Idempotent: each column is guarded with hasColumn so the migration is safe
 * to re-run on the live server.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rfps', function (Blueprint $table) {
            if (!Schema::hasColumn('rfps', 'procurement_compliance')) {
                // 'followed' = procedures duly followed; 'waived' = process waived.
                $table->enum('procurement_compliance', ['followed', 'waived'])->nullable()->after('status');
            }
            if (!Schema::hasColumn('rfps', 'compliance_justification')) {
                $table->text('compliance_justification')->nullable()->after('procurement_compliance');
            }
            if (!Schema::hasColumn('rfps', 'compliance_document_url')) {
                // Link/reference to the justification document (no upload subsystem yet).
                $table->string('compliance_document_url', 2000)->nullable()->after('compliance_justification');
            }
            if (!Schema::hasColumn('rfps', 'compliance_confirmed_by')) {
                $table->foreignUuid('compliance_confirmed_by')->nullable()->after('compliance_document_url')
                    ->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('rfps', 'compliance_confirmed_at')) {
                $table->timestamp('compliance_confirmed_at')->nullable()->after('compliance_confirmed_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('rfps', function (Blueprint $table) {
            if (Schema::hasColumn('rfps', 'compliance_confirmed_by')) {
                $table->dropConstrainedForeignId('compliance_confirmed_by');
            }
            foreach (['procurement_compliance', 'compliance_justification', 'compliance_document_url', 'compliance_confirmed_at'] as $col) {
                if (Schema::hasColumn('rfps', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
