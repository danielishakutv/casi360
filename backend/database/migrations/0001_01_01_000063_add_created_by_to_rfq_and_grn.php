<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a nullable `created_by` user FK to the rfqs and grns tables and
 * backfills it from the global audit_logs table for legacy rows.
 *
 * Why: those two tables had no per-user identity column, so the personal
 * scope filter on /procurement/rfq and /procurement/grn relied entirely on
 * audit_logs entries. That covered every row created after Phase 2's
 * audit-logging was already in place — but legacy rows whose `*_created`
 * audit-log entry pre-dates the scope filter, or whose audit row was lost,
 * would be invisible to non-view_all users. With `created_by` populated,
 * the scope predicate has a stable, indexed signal to match against.
 *
 * Idempotent and safe to re-run on production: column adds are guarded by
 * Schema::hasColumn, and the backfill only touches rows where created_by
 * is currently NULL.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('rfqs', 'created_by')) {
            Schema::table('rfqs', function (Blueprint $table) {
                $table->foreignUuid('created_by')
                      ->nullable()
                      ->after('rfq_number')
                      ->constrained('users')
                      ->nullOnDelete();
                $table->index('created_by', 'rfqs_created_by_idx');
            });
        }

        if (!Schema::hasColumn('grns', 'created_by')) {
            Schema::table('grns', function (Blueprint $table) {
                $table->foreignUuid('created_by')
                      ->nullable()
                      ->after('grn_number')
                      ->constrained('users')
                      ->nullOnDelete();
                $table->index('created_by', 'grns_created_by_idx');
            });
        }

        // Backfill from audit_logs. The earliest <type>_created entry per
        // entity is the creator. We use a JOIN against an aggregated
        // subquery so this runs as a single statement on production.
        $this->backfillCreatorsFromAuditLogs('rfqs', 'rfq');
        $this->backfillCreatorsFromAuditLogs('grns', 'grn');
    }

    public function down(): void
    {
        if (Schema::hasColumn('rfqs', 'created_by')) {
            Schema::table('rfqs', function (Blueprint $table) {
                $table->dropForeign(['created_by']);
                $table->dropIndex('rfqs_created_by_idx');
                $table->dropColumn('created_by');
            });
        }

        if (Schema::hasColumn('grns', 'created_by')) {
            Schema::table('grns', function (Blueprint $table) {
                $table->dropForeign(['created_by']);
                $table->dropIndex('grns_created_by_idx');
                $table->dropColumn('created_by');
            });
        }
    }

    /**
     * Set $table.created_by from the user_id of the earliest
     * `<entityType>_created` audit_logs entry for each row.
     *
     * Uses a correlated subquery with ORDER BY + LIMIT 1 so it runs on
     * both MySQL (production) and SQLite (the in-memory test database).
     * If multiple audit entries exist (defensive — shouldn't happen for
     * `_created`), the earliest wins. Rows with no matching audit entry
     * stay NULL: the scope filter falls back to the audit-log predicate
     * for those, exactly as it did before this migration.
     */
    private function backfillCreatorsFromAuditLogs(string $table, string $entityType): void
    {
        $action = $entityType . '_created';

        DB::statement("
            UPDATE {$table}
            SET created_by = (
                SELECT user_id FROM audit_logs
                WHERE entity_type = ?
                  AND action = ?
                  AND user_id IS NOT NULL
                  AND entity_id = {$table}.id
                ORDER BY created_at ASC
                LIMIT 1
            )
            WHERE created_by IS NULL
        ", [$entityType, $action]);
    }
};
