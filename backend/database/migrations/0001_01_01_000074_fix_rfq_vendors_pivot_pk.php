<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Drop the spurious `id` primary key on rfq_vendors.
 *
 * Migration 0073 created the pivot with a UUID `id` PK. Eloquent's
 * BelongsToMany::sync() only inserts (rfq_id, vendor_id, created_at,
 * updated_at) — it never supplies an `id` — so MySQL rejected every
 * insert because the PK column had no default value. Net effect: any
 * RFQ create with at least one vendor 500ed inside the transaction
 * (and the browser surfaced it as a CORS failure because the response
 * never reached the CORS middleware).
 *
 * Pivot tables don't need their own surrogate key. The (rfq_id,
 * vendor_id) pair is naturally unique and is exactly what Laravel
 * expects to insert. Promoting it to PK and dropping `id` makes
 * sync() work without further code changes.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('rfq_vendors')) {
            return;
        }

        // Drop the redundant unique index first — the same column pair
        // is about to become the PK, and MySQL rejects a PK on columns
        // already covered by an identical unique index in some versions.
        $hasUnique = DB::selectOne(
            "SHOW INDEX FROM `rfq_vendors` WHERE Key_name = 'rfq_vendors_rfq_id_vendor_id_unique'"
        );
        if ($hasUnique) {
            DB::statement('ALTER TABLE `rfq_vendors` DROP INDEX `rfq_vendors_rfq_id_vendor_id_unique`');
        }

        // Drop the surrogate id PK + column and promote (rfq_id,
        // vendor_id) in a single ALTER TABLE so the table is never
        // left without a primary key.
        $hasIdColumn = Schema::hasColumn('rfq_vendors', 'id');
        if ($hasIdColumn) {
            DB::statement(
                'ALTER TABLE `rfq_vendors` '
                . 'DROP PRIMARY KEY, '
                . 'DROP COLUMN `id`, '
                . 'ADD PRIMARY KEY (`rfq_id`, `vendor_id`)'
            );
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('rfq_vendors')) {
            return;
        }

        // Restoring the surrogate PK would require backfilling a UUID
        // for every existing row, which is messy in pure SQL. Since
        // the original surrogate-key layout was the bug, we don't try
        // to recreate it on rollback — we just drop the composite PK
        // and re-add the unique index so the table is queryable again.
        DB::statement('ALTER TABLE `rfq_vendors` DROP PRIMARY KEY');
        DB::statement(
            'ALTER TABLE `rfq_vendors` '
            . 'ADD UNIQUE `rfq_vendors_rfq_id_vendor_id_unique` (`rfq_id`, `vendor_id`)'
        );
    }
};
