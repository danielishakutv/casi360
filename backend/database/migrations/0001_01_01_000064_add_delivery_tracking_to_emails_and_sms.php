<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds honest delivery tracking columns to the emails and sms_messages
 * tables. Before this migration both controllers set status='sent' the
 * moment a record was inserted, even if the underlying mailer / SMS
 * gateway was unavailable. With these columns we can record per-record
 * success and failure counts, store the last error for diagnostics, and
 * set a status that reflects what actually happened.
 *
 * Idempotent: every column add is guarded by Schema::hasColumn so this
 * is safe to run on a production DB that may have been touched already.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('emails', function (Blueprint $table) {
            if (!Schema::hasColumn('emails', 'delivered_count')) {
                $table->unsignedInteger('delivered_count')->default(0)->after('recipient_count');
            }
            if (!Schema::hasColumn('emails', 'failed_count')) {
                $table->unsignedInteger('failed_count')->default(0)->after('delivered_count');
            }
            if (!Schema::hasColumn('emails', 'error_message')) {
                $table->text('error_message')->nullable()->after('failed_count');
            }
        });

        Schema::table('sms_messages', function (Blueprint $table) {
            if (!Schema::hasColumn('sms_messages', 'delivered_count')) {
                $table->unsignedInteger('delivered_count')->default(0)->after('recipient_count');
            }
            if (!Schema::hasColumn('sms_messages', 'failed_count')) {
                $table->unsignedInteger('failed_count')->default(0)->after('delivered_count');
            }
            if (!Schema::hasColumn('sms_messages', 'error_message')) {
                $table->text('error_message')->nullable()->after('failed_count');
            }
        });
    }

    public function down(): void
    {
        Schema::table('emails', function (Blueprint $table) {
            foreach (['delivered_count', 'failed_count', 'error_message'] as $col) {
                if (Schema::hasColumn('emails', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('sms_messages', function (Blueprint $table) {
            foreach (['delivered_count', 'failed_count', 'error_message'] as $col) {
                if (Schema::hasColumn('sms_messages', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
