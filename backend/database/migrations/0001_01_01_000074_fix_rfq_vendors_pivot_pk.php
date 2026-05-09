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
 *
 * MySQL refuses to drop the composite unique index directly because
 * it's the backing index for the `rfq_id` foreign key (no standalone
 * rfq_id index exists). So we drop the FK first, reshape the table,
 * then re-add the FK on top of the new composite PK (which leads
 * with rfq_id and serves as the backing index automatically).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('rfq_vendors')) {
            return;
        }

        // Look up the rfq_id FK by inspecting information_schema rather
        // than assuming Laravel's default constraint name — keeps the
        // migration robust if the constraint was named differently.
        $rfqFk = DB::selectOne(
            "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'rfq_vendors'
               AND COLUMN_NAME = 'rfq_id'
               AND REFERENCED_TABLE_NAME = 'rfqs'
             LIMIT 1"
        );

        if ($rfqFk) {
            DB::statement("ALTER TABLE `rfq_vendors` DROP FOREIGN KEY `{$rfqFk->CONSTRAINT_NAME}`");
        }

        // Now safe to drop the composite unique — its backing FK is gone.
        $hasUnique = DB::selectOne(
            "SHOW INDEX FROM `rfq_vendors` WHERE Key_name = 'rfq_vendors_rfq_id_vendor_id_unique'"
        );
        if ($hasUnique) {
            DB::statement('ALTER TABLE `rfq_vendors` DROP INDEX `rfq_vendors_rfq_id_vendor_id_unique`');
        }

        // Drop the surrogate id PK + column and promote (rfq_id,
        // vendor_id) in one ALTER so the table is never left without
        // a primary key.
        if (Schema::hasColumn('rfq_vendors', 'id')) {
            DB::statement(
                'ALTER TABLE `rfq_vendors` '
                . 'DROP PRIMARY KEY, '
                . 'DROP COLUMN `id`, '
                . 'ADD PRIMARY KEY (`rfq_id`, `vendor_id`)'
            );
        }

        // Re-add the FK. The new composite PK leads with rfq_id and
        // serves as the backing index, so no separate index is needed.
        if ($rfqFk) {
            DB::statement(
                "ALTER TABLE `rfq_vendors` "
                . "ADD CONSTRAINT `{$rfqFk->CONSTRAINT_NAME}` "
                . "FOREIGN KEY (`rfq_id`) REFERENCES `rfqs` (`id`) ON DELETE CASCADE"
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
