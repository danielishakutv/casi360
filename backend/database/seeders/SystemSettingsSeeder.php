<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            /*
            |------------------------------------------------------------------
            | Organization
            |------------------------------------------------------------------
            */
            [
                'group'       => 'organization',
                'key'         => 'organization_name',
                'value'       => 'CASI',
                'type'        => 'string',
                'label'       => 'Organization Name',
                'description' => 'Full name of the organization.',
                'is_public'   => true,
            ],
            [
                'group'       => 'organization',
                'key'         => 'organization_acronym',
                'value'       => 'CASI',
                'type'        => 'string',
                'label'       => 'Organization Acronym',
                'description' => 'Short acronym displayed in the header and sidebar.',
                'is_public'   => true,
            ],
            [
                'group'       => 'organization',
                'key'         => 'organization_email',
                'value'       => 'info@casi.org',
                'type'        => 'string',
                'label'       => 'Contact Email',
                'description' => 'Primary contact email address.',
                'is_public'   => true,
            ],
            [
                'group'       => 'organization',
                'key'         => 'organization_phone',
                'value'       => null,
                'type'        => 'string',
                'label'       => 'Contact Phone',
                'description' => 'Primary phone number.',
                'is_public'   => true,
            ],
            [
                'group'       => 'organization',
                'key'         => 'organization_address',
                'value'       => null,
                'type'        => 'text',
                'label'       => 'Address',
                'description' => 'Full street address.',
                'is_public'   => true,
            ],
            [
                'group'       => 'organization',
                'key'         => 'organization_city',
                'value'       => null,
                'type'        => 'string',
                'label'       => 'City',
                'description' => 'City or municipality.',
                'is_public'   => true,
            ],
            [
                'group'       => 'organization',
                'key'         => 'organization_state',
                'value'       => null,
                'type'        => 'string',
                'label'       => 'State / Province',
                'description' => 'State, province, or region.',
                'is_public'   => true,
            ],
            [
                'group'       => 'organization',
                'key'         => 'organization_country',
                'value'       => null,
                'type'        => 'string',
                'label'       => 'Country',
                'description' => 'Country name.',
                'is_public'   => true,
            ],
            [
                'group'       => 'organization',
                'key'         => 'organization_website',
                'value'       => 'https://casi360.com',
                'type'        => 'string',
                'label'       => 'Website URL',
                'description' => 'Organization website.',
                'is_public'   => true,
            ],
            [
                'group'       => 'organization',
                'key'         => 'organization_logo_url',
                'value'       => null,
                'type'        => 'string',
                'label'       => 'Logo URL',
                'description' => 'URL to the organization logo image.',
                'is_public'   => true,
            ],
            [
                'group'       => 'organization',
                'key'         => 'organization_registration_number',
                'value'       => null,
                'type'        => 'string',
                'label'       => 'Registration Number',
                'description' => 'Official registration number with the regulatory authority.',
                'is_public'   => true,
            ],
            [
                'group'       => 'organization',
                'key'         => 'organization_tax_id',
                'value'       => null,
                'type'        => 'string',
                'label'       => 'Tax ID (TIN)',
                'description' => 'Tax identification number / TIN.',
                'is_public'   => false,
            ],
            [
                'group'       => 'organization',
                'key'         => 'organization_founded',
                'value'       => null,
                'type'        => 'string',
                'label'       => 'Year Founded',
                'description' => 'Year the organization was established.',
                'is_public'   => true,
            ],

            /*
            |------------------------------------------------------------------
            | Localization
            |------------------------------------------------------------------
            */
            [
                'group'       => 'localization',
                'key'         => 'timezone',
                'value'       => 'Africa/Lagos',
                'type'        => 'string',
                'label'       => 'Timezone',
                'description' => 'Default timezone for date/time display across the app.',
                'is_public'   => false,
            ],
            [
                'group'       => 'localization',
                'key'         => 'date_format',
                'value'       => 'DD/MM/YYYY',
                'type'        => 'string',
                'label'       => 'Date Format',
                'description' => 'Display format for dates. DD = day, MM = month, YYYY = year.',
                'is_public'   => false,
            ],
            [
                'group'       => 'localization',
                'key'         => 'time_format',
                'value'       => '12h',
                'type'        => 'string',
                'label'       => 'Time Format',
                'description' => '12-hour or 24-hour clock.',
                'is_public'   => false,
            ],
            [
                'group'       => 'localization',
                'key'         => 'currency',
                'value'       => 'NGN',
                'type'        => 'string',
                'label'       => 'Currency',
                'description' => 'Default currency code (ISO 4217).',
                'is_public'   => false,
            ],
            [
                'group'       => 'localization',
                'key'         => 'currency_symbol',
                'value'       => '₦',
                'type'        => 'string',
                'label'       => 'Currency Symbol',
                'description' => 'Symbol displayed with monetary values.',
                'is_public'   => false,
            ],
            [
                'group'       => 'localization',
                'key'         => 'language',
                'value'       => 'en',
                'type'        => 'string',
                'label'       => 'Language',
                'description' => 'Default interface language.',
                'is_public'   => false,
            ],

            /*
            |------------------------------------------------------------------
            | Appearance
            |------------------------------------------------------------------
            */
            [
                'group'       => 'appearance',
                'key'         => 'primary_color',
                'value'       => '#1E40AF',
                'type'        => 'string',
                'label'       => 'Primary Color',
                'description' => 'Primary brand color (hex).',
                'is_public'   => true,
            ],
            [
                'group'       => 'appearance',
                'key'         => 'accent_color',
                'value'       => '#F59E0B',
                'type'        => 'string',
                'label'       => 'Accent Color',
                'description' => 'Accent/highlight color (hex).',
                'is_public'   => true,
            ],
            [
                'group'       => 'appearance',
                'key'         => 'login_bg_url',
                'value'       => null,
                'type'        => 'string',
                'label'       => 'Login Background Image',
                'description' => 'URL for the login page background image.',
                'is_public'   => true,
            ],
            [
                'group'       => 'appearance',
                'key'         => 'favicon_url',
                'value'       => null,
                'type'        => 'string',
                'label'       => 'Favicon URL',
                'description' => 'URL to the favicon image.',
                'is_public'   => true,
            ],

            /*
            |------------------------------------------------------------------
            | System
            |------------------------------------------------------------------
            */
            [
                'group'       => 'system',
                'key'         => 'maintenance_mode',
                'value'       => '0',
                'type'        => 'boolean',
                'label'       => 'Maintenance Mode',
                'description' => 'When enabled, non-admin users see a maintenance page.',
                'is_public'   => true,
            ],
            [
                'group'       => 'system',
                'key'         => 'maintenance_message',
                'value'       => 'The system is currently undergoing maintenance. Please try again later.',
                'type'        => 'text',
                'label'       => 'Maintenance Message',
                'description' => 'Message displayed to users during maintenance.',
                'is_public'   => true,
            ],
            [
                'group'       => 'system',
                'key'         => 'session_lifetime_minutes',
                'value'       => '120',
                'type'        => 'integer',
                'label'       => 'Session Lifetime (minutes)',
                'description' => 'How long a user session stays active before expiring.',
                'is_public'   => false,
            ],
            [
                'group'       => 'system',
                'key'         => 'pagination_default',
                'value'       => '15',
                'type'        => 'integer',
                'label'       => 'Default Page Size',
                'description' => 'Default number of items per page in list views.',
                'is_public'   => false,
            ],
            [
                'group'       => 'system',
                'key'         => 'allow_self_registration',
                'value'       => '0',
                'type'        => 'boolean',
                'label'       => 'Allow Self Registration',
                'description' => 'Whether users can register without an admin creating their account.',
                'is_public'   => true,
            ],
            [
                'group'       => 'system',
                'key'         => 'password_min_length',
                'value'       => '8',
                'type'        => 'integer',
                'label'       => 'Minimum Password Length',
                'description' => 'Minimum number of characters required for passwords.',
                'is_public'   => false,
            ],
            [
                'group'       => 'system',
                'key'         => 'max_login_attempts',
                'value'       => '5',
                'type'        => 'integer',
                'label'       => 'Max Login Attempts',
                'description' => 'Number of failed login attempts before rate limiting.',
                'is_public'   => false,
            ],

            /*
            |------------------------------------------------------------------
            | Notifications
            |------------------------------------------------------------------
            */
            [ 'group' => 'notifications', 'key' => 'notifications_email_alerts',    'value' => '1', 'type' => 'boolean', 'label' => 'Email Alerts',         'description' => 'Send important notifications by email.', 'is_public' => false ],
            [ 'group' => 'notifications', 'key' => 'notifications_sms_alerts',      'value' => '0', 'type' => 'boolean', 'label' => 'SMS Alerts',           'description' => 'Send notifications by SMS (requires SMS gateway).', 'is_public' => false ],
            [ 'group' => 'notifications', 'key' => 'notifications_push',            'value' => '1', 'type' => 'boolean', 'label' => 'Push Notifications',   'description' => 'Browser push notifications when the app is open.', 'is_public' => false ],
            [ 'group' => 'notifications', 'key' => 'notifications_weekly_digest',   'value' => '1', 'type' => 'boolean', 'label' => 'Weekly Digest',        'description' => 'Email summary of activity every Monday.', 'is_public' => false ],
            [ 'group' => 'notifications', 'key' => 'notifications_mention_alerts',  'value' => '1', 'type' => 'boolean', 'label' => 'Mention Alerts',       'description' => 'Notify when someone mentions you.', 'is_public' => false ],
            [ 'group' => 'notifications', 'key' => 'notifications_approval_alerts', 'value' => '1', 'type' => 'boolean', 'label' => 'Approval Requests',    'description' => 'Notify when an item needs your approval.', 'is_public' => false ],
            [ 'group' => 'notifications', 'key' => 'notifications_system_updates',  'value' => '0', 'type' => 'boolean', 'label' => 'System Updates',       'description' => 'Platform updates and maintenance notices.', 'is_public' => false ],
            [ 'group' => 'notifications', 'key' => 'notifications_security_alerts', 'value' => '1', 'type' => 'boolean', 'label' => 'Security Alerts',      'description' => 'Suspicious activity, failed logins, role changes.', 'is_public' => false ],

            /*
            |------------------------------------------------------------------
            | Security (the session_lifetime_minutes / max_login_attempts
            | rows live in the system group above; these are the extras
            | the /settings page's Security tab exposes.)
            |------------------------------------------------------------------
            */
            [ 'group' => 'security', 'key' => 'security_two_factor',       'value' => '0',  'type' => 'boolean', 'label' => 'Two-Factor Authentication', 'description' => 'Require a second verification step on login.', 'is_public' => false ],
            [ 'group' => 'security', 'key' => 'security_password_expiry',  'value' => '90', 'type' => 'integer', 'label' => 'Password Expiry (days)',    'description' => 'Force password reset after this many days. 0 = never.', 'is_public' => false ],
            [ 'group' => 'security', 'key' => 'security_ip_whitelist',     'value' => null, 'type' => 'string',  'label' => 'IP Whitelist',              'description' => 'Comma-separated CIDR ranges allowed to sign in. Leave blank for unrestricted.', 'is_public' => false ],
            [ 'group' => 'security', 'key' => 'security_strong_passwords', 'value' => '1',  'type' => 'boolean', 'label' => 'Enforce Strong Passwords',  'description' => 'Min 8 chars with uppercase, number, and special character.', 'is_public' => false ],

            /*
            |------------------------------------------------------------------
            | Procurement
            |------------------------------------------------------------------
            */
            [
                'group'       => 'procurement',
                'key'         => 'procurement.approval.operations_threshold',
                'value'       => '500000',
                'type'        => 'integer',
                'label'       => 'Operations Approval Threshold',
                'description' => 'Amount (in base currency) above which Operations Officer approval is required.',
                'is_public'   => false,
            ],
            [
                'group'       => 'procurement',
                'key'         => 'procurement.approval.executive_threshold',
                'value'       => '1000000',
                'type'        => 'integer',
                'label'       => 'Executive Director Approval Threshold',
                'description' => 'Amount (in base currency) above which Executive Director approval is required.',
                'is_public'   => false,
            ],
            [
                'group'       => 'procurement',
                'key'         => 'procurement.approval.block_self_approval',
                'value'       => '1',
                'type'        => 'boolean',
                'label'       => 'Block Self-Approval',
                'description' => 'When enabled, users cannot approve their own submissions unless their role has the self_approve permission.',
                'is_public'   => false,
            ],
        ];

        $count = 0;
        foreach ($settings as $setting) {
            SystemSetting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
            $count++;
        }

        $this->command->info("System settings seeded: {$count} settings.");
    }
}
