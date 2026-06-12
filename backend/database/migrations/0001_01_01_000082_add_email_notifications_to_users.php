<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-user "receive email notifications" preference.
 *
 * Complements the org-wide kill switch (notifications_email_alerts): a user can
 * turn OFF their own email alerts while in-app notifications keep working. The
 * Notifier respects this when sending mail. Defaults to true (opt-out model).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('email_notifications')->default(true)->after('force_password_change');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('email_notifications');
        });
    }
};
