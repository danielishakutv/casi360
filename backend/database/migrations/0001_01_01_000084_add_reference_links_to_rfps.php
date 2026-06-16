<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Link a Payment Request (RFP) to multiple PR / PO / GRN references.
 *
 * The create form previously had free-text PR/PO/GRN number boxes that were
 * never persisted (the payload keys didn't match the API). These JSON columns
 * store the reference numbers chosen from the new searchable multi-select
 * dropdowns. The existing singular po_reference / grn_reference columns stay
 * as the canonical document-chain link and are mirrored from the first
 * selected value. Additive + idempotent.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rfps', function (Blueprint $table) {
            if (!Schema::hasColumn('rfps', 'pr_references')) {
                $table->json('pr_references')->nullable()->after('grn_reference');
            }
            if (!Schema::hasColumn('rfps', 'po_references')) {
                $table->json('po_references')->nullable()->after('pr_references');
            }
            if (!Schema::hasColumn('rfps', 'grn_references')) {
                $table->json('grn_references')->nullable()->after('po_references');
            }
        });
    }

    public function down(): void
    {
        Schema::table('rfps', function (Blueprint $table) {
            foreach (['pr_references', 'po_references', 'grn_references'] as $col) {
                if (Schema::hasColumn('rfps', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
