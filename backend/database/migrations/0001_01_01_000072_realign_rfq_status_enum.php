<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Realign the RFQ status lifecycle.
 *
 * The frontend RFQ list, status filter, and badge styling all assume
 *   draft → open → closed → awarded (with cancelled as a side-exit)
 * but the database column was the original procurement-flow enum:
 *   draft, sent, received, evaluated, cancelled.
 *
 * Picking 'open' from the form was silently rejected by the API (422
 * on the enum check). This migration fixes it up:
 *   - Maps every existing row onto the new vocabulary so we don't lose
 *     data when MySQL replaces the column.
 *   - Rewrites the enum to the new set with 'open' as default.
 *
 * Mapping:
 *   sent      → open    (issued to vendors but no quote returned)
 *   received  → closed  (quote(s) in, no longer accepting submissions)
 *   evaluated → awarded (final decision recorded)
 *   draft     → draft
 *   cancelled → cancelled
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('rfqs')) {
            return;
        }

        // Backfill rows onto the new vocabulary BEFORE the enum is rewritten,
        // otherwise MySQL refuses values that aren't in the current column
        // definition.
        DB::table('rfqs')->where('status', 'sent')->update(['status' => 'open']);
        DB::table('rfqs')->where('status', 'received')->update(['status' => 'closed']);
        DB::table('rfqs')->where('status', 'evaluated')->update(['status' => 'awarded']);

        // MODIFY COLUMN keeps existing data and changes the enum + default
        // in one step. Using a raw statement because Doctrine's schema
        // builder doesn't model MySQL enums well.
        DB::statement(
            "ALTER TABLE `rfqs` MODIFY COLUMN `status` "
            . "ENUM('draft','open','closed','awarded','cancelled') "
            . "NOT NULL DEFAULT 'open'"
        );
    }

    public function down(): void
    {
        if (!Schema::hasTable('rfqs')) {
            return;
        }

        // Reverse mapping for rollback.
        DB::table('rfqs')->where('status', 'open')->update(['status' => 'sent']);
        DB::table('rfqs')->where('status', 'closed')->update(['status' => 'received']);
        DB::table('rfqs')->where('status', 'awarded')->update(['status' => 'evaluated']);

        DB::statement(
            "ALTER TABLE `rfqs` MODIFY COLUMN `status` "
            . "ENUM('draft','sent','received','evaluated','cancelled') "
            . "NOT NULL DEFAULT 'draft'"
        );
    }
};
