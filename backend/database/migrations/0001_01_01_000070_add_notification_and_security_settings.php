<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Adds the system_settings rows the /settings page's Notifications and
 * Security tabs have been sending all along — bulk-update was 422'ing
 * with "settings not found" because the keys never existed in the DB.
 *
 * Notifications: net-new group with 8 boolean toggles.
 * Security: net-new group with 4 keys; the other two (session_timeout,
 * max_login_attempts) are intentionally NOT duplicated here — the
 * frontend has been re-keyed to read/write the canonical
 * `session_lifetime_minutes` and `max_login_attempts` rows that already
 * live in the `system` group.
 *
 * Idempotent — re-running is a no-op for rows that already exist, and
 * any value the super admin set in the meantime is preserved.
 */
return new class extends Migration
{
    private const ROWS = [
        // ── Notifications ──────────────────────────────────────────
        [
            'group' => 'notifications', 'key' => 'notifications_email_alerts',
            'value' => '1', 'type' => 'boolean', 'is_public' => false,
            'label' => 'Email Alerts', 'description' => 'Send important notifications by email.',
        ],
        [
            'group' => 'notifications', 'key' => 'notifications_sms_alerts',
            'value' => '0', 'type' => 'boolean', 'is_public' => false,
            'label' => 'SMS Alerts', 'description' => 'Send notifications by SMS (requires SMS gateway).',
        ],
        [
            'group' => 'notifications', 'key' => 'notifications_push',
            'value' => '1', 'type' => 'boolean', 'is_public' => false,
            'label' => 'Push Notifications', 'description' => 'Browser push notifications when the app is open.',
        ],
        [
            'group' => 'notifications', 'key' => 'notifications_weekly_digest',
            'value' => '1', 'type' => 'boolean', 'is_public' => false,
            'label' => 'Weekly Digest', 'description' => 'Email summary of activity every Monday.',
        ],
        [
            'group' => 'notifications', 'key' => 'notifications_mention_alerts',
            'value' => '1', 'type' => 'boolean', 'is_public' => false,
            'label' => 'Mention Alerts', 'description' => 'Notify when someone mentions you.',
        ],
        [
            'group' => 'notifications', 'key' => 'notifications_approval_alerts',
            'value' => '1', 'type' => 'boolean', 'is_public' => false,
            'label' => 'Approval Requests', 'description' => 'Notify when an item needs your approval.',
        ],
        [
            'group' => 'notifications', 'key' => 'notifications_system_updates',
            'value' => '0', 'type' => 'boolean', 'is_public' => false,
            'label' => 'System Updates', 'description' => 'Platform updates and maintenance notices.',
        ],
        [
            'group' => 'notifications', 'key' => 'notifications_security_alerts',
            'value' => '1', 'type' => 'boolean', 'is_public' => false,
            'label' => 'Security Alerts', 'description' => 'Suspicious activity, failed logins, role changes.',
        ],

        // ── Security extras (the others map to existing system.* rows) ─
        [
            'group' => 'security', 'key' => 'security_two_factor',
            'value' => '0', 'type' => 'boolean', 'is_public' => false,
            'label' => 'Two-Factor Authentication', 'description' => 'Require a second verification step on login.',
        ],
        [
            'group' => 'security', 'key' => 'security_password_expiry',
            'value' => '90', 'type' => 'integer', 'is_public' => false,
            'label' => 'Password Expiry (days)', 'description' => 'Force password reset after this many days. 0 = never.',
        ],
        [
            'group' => 'security', 'key' => 'security_ip_whitelist',
            'value' => null, 'type' => 'string', 'is_public' => false,
            'label' => 'IP Whitelist', 'description' => 'Comma-separated CIDR ranges allowed to sign in. Leave blank for unrestricted.',
        ],
        [
            'group' => 'security', 'key' => 'security_strong_passwords',
            'value' => '1', 'type' => 'boolean', 'is_public' => false,
            'label' => 'Enforce Strong Passwords', 'description' => 'Min 8 chars with uppercase, number, and special character.',
        ],
    ];

    public function up(): void
    {
        foreach (self::ROWS as $row) {
            $exists = DB::table('system_settings')->where('key', $row['key'])->exists();
            if ($exists) {
                continue;
            }
            DB::table('system_settings')->insert([
                'id'          => (string) Str::uuid(),
                'group'       => $row['group'],
                'key'         => $row['key'],
                'value'       => $row['value'],
                'type'        => $row['type'],
                'label'       => $row['label'],
                'description' => $row['description'],
                'is_public'   => $row['is_public'],
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }
    }

    public function down(): void
    {
        $keys = array_column(self::ROWS, 'key');
        DB::table('system_settings')->whereIn('key', $keys)->delete();
    }
};
