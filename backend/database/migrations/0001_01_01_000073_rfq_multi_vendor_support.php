<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Multi-vendor + open-call support for RFQs.
 *
 * Real procurement RFQs go to multiple vendors at once and the result
 * is compared across responses. The original schema pinned a single
 * `vendor_id` per RFQ, which forced duplicate records when soliciting
 * more than one vendor. This migration:
 *
 *   - adds a `scope` discriminator (`targeted` | `open`) so an RFQ can
 *     either name a specific list of vendors or be a public/open call
 *   - adds `advertised_on` (free text) for open calls — where the RFQ
 *     was advertised so there's still an audit trail of how vendors
 *     were reached
 *   - introduces an `rfq_vendors` pivot listing every vendor invited
 *     for a given RFQ
 *   - backfills the pivot from existing single-vendor rows so legacy
 *     data round-trips through the new model
 *
 * The old `vendor_id` column is kept (as the "primary recipient") for
 * backward compatibility with downstream code that still reads it (PO
 * creation, etc.). The pivot is the source of truth going forward.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('rfqs')) {
            return;
        }

        Schema::table('rfqs', function (Blueprint $table) {
            if (!Schema::hasColumn('rfqs', 'scope')) {
                $table->enum('scope', ['targeted', 'open'])
                    ->default('targeted')
                    ->after('status');
            }
            if (!Schema::hasColumn('rfqs', 'advertised_on')) {
                $table->text('advertised_on')->nullable()->after('scope');
            }
        });

        if (!Schema::hasTable('rfq_vendors')) {
            Schema::create('rfq_vendors', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('rfq_id');
                $table->uuid('vendor_id');
                $table->timestamps();

                $table->foreign('rfq_id')->references('id')->on('rfqs')->cascadeOnDelete();
                $table->foreign('vendor_id')->references('id')->on('vendors')->cascadeOnDelete();
                $table->unique(['rfq_id', 'vendor_id']);
                $table->index('vendor_id');
            });
        }

        // Backfill: every existing RFQ that had a single vendor pinned
        // gets that vendor in the pivot too. insertOrIgnore is a no-op
        // if the migration is re-run.
        DB::table('rfqs')
            ->whereNotNull('vendor_id')
            ->select('id', 'vendor_id')
            ->orderBy('created_at')
            ->chunk(200, function ($rows) {
                $now = now();
                $insert = [];
                foreach ($rows as $row) {
                    $insert[] = [
                        'id' => (string) Str::uuid(),
                        'rfq_id' => $row->id,
                        'vendor_id' => $row->vendor_id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
                if (!empty($insert)) {
                    DB::table('rfq_vendors')->insertOrIgnore($insert);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('rfq_vendors');

        if (!Schema::hasTable('rfqs')) {
            return;
        }

        Schema::table('rfqs', function (Blueprint $table) {
            if (Schema::hasColumn('rfqs', 'advertised_on')) {
                $table->dropColumn('advertised_on');
            }
            if (Schema::hasColumn('rfqs', 'scope')) {
                $table->dropColumn('scope');
            }
        });
    }
};
