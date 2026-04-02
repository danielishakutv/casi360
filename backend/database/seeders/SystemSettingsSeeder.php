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
                'key'         => 'org_name',
                'value'       => 'CASI',
                'type'        => 'string',
                'label'       => 'Organization Name',
                'description' => 'Full name of the organization.',
                'is_public'   => true,
            ],
            [
                'group'       => 'organization',
                'key'         => 'org_acronym',
                'value'       => 'CASI',
                'type'        => 'string',
                'label'       => 'Organization Acronym',
                'description' => 'Short acronym displayed in the header and sidebar.',
                'is_public'   => true,
            ],
            [
                'group'       => 'organization',
                'key'         => 'org_email',
                'value'       => 'info@casi.org',
                'type'        => 'string',
                'label'       => 'Contact Email',
                'description' => 'Primary contact email address.',
                'is_public'   => true,
            ],
            [
                'group'       => 'organization',
                'key'         => 'org_phone',
                'value'       => null,
                'type'        => 'string',
                'label'       => 'Contact Phone',
                'description' => 'Primary phone number.',
                'is_public'   => true,
            ],
            [
                'group'       => 'organization',
                'key'         => 'org_address',
                'value'       => null,
                'type'        => 'text',
                'label'       => 'Address',
                'description' => 'Full street address.',
                'is_public'   => true,
            ],
            [
                'group'       => 'organization',
                'key'         => 'org_city',
                'value'       => null,
                'type'        => 'string',
                'label'       => 'City',
                'description' => 'City or municipality.',
                'is_public'   => true,
            ],
            [
                'group'       => 'organization',
                'key'         => 'org_state',
                'value'       => null,
                'type'        => 'string',
                'label'       => 'State / Province',
                'description' => 'State, province, or region.',
                'is_public'   => true,
            ],
            [
                'group'       => 'organization',
                'key'         => 'org_country',
                'value'       => null,
                'type'        => 'string',
                'label'       => 'Country',
                'description' => 'Country name.',
                'is_public'   => true,
            ],
            [
                'group'       => 'organization',
                'key'         => 'org_website',
                'value'       => 'https://casi360.com',
                'type'        => 'string',
                'label'       => 'Website URL',
                'description' => 'Organization website.',
                'is_public'   => true,
            ],
            [
                'group'       => 'organization',
                'key'         => 'org_logo_url',
                'value'       => null,
                'type'        => 'string',
                'label'       => 'Logo URL',
                'description' => 'URL to the organization logo image.',
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
                'value'       => 'America/New_York',
                'type'        => 'string',
                'label'       => 'Timezone',
                'description' => 'Default timezone for date/time display.',
                'is_public'   => false,
            ],
            [
                'group'       => 'localization',
                'key'         => 'date_format',
                'value'       => 'MM/DD/YYYY',
                'type'        => 'string',
                'label'       => 'Date Format',
                'description' => 'Display format for dates.',
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
                'value'       => 'USD',
                'type'        => 'string',
                'label'       => 'Currency',
                'description' => 'Default currency code (ISO 4217).',
                'is_public'   => false,
            ],
            [
                'group'       => 'localization',
                'key'         => 'currency_symbol',
                'value'       => '$',
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
                'description' => 'Default system language.',
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
