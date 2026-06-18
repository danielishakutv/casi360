<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Direct-message email throttle. Records when we last emailed a user about a
 * new DM so a burst of messages results in at most one email per window
 * (see config/notifications.php). Additive + idempotent.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'last_message_email_at')) {
                $table->timestamp('last_message_email_at')->nullable()->after('last_login_ip');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'last_message_email_at')) {
                $table->dropColumn('last_message_email_at');
            }
        });
    }
};
