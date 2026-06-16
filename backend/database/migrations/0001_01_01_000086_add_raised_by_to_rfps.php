<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * v2 §4 (Key Compliance Principles) — Segregation of duties.
 *
 * Record which user raised a Payment Request so the approval engine can stop
 * the raiser from also approving it (requester ≠ approver). PRs already carry
 * requested_by / submitted_by; RFPs had no equivalent. Additive + idempotent.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rfps', function (Blueprint $table) {
            if (!Schema::hasColumn('rfps', 'raised_by')) {
                $table->foreignUuid('raised_by')->nullable()->after('rfp_number')
                    ->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('rfps', function (Blueprint $table) {
            if (Schema::hasColumn('rfps', 'raised_by')) {
                $table->dropConstrainedForeignId('raised_by');
            }
        });
    }
};
