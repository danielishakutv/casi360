<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Renames the organization-group system_settings keys from `org_*` to
 * `organization_*` so they match the frontend Settings page, which has
 * always used the longer naming convention. Before this migration the
 * Organization tab silently 422'd on save because the bulk-update
 * endpoint requires every submitted key to exist and the payload keys
 * never matched the seeded ones.
 *
 * Also seeds three previously-missing keys (registration_number,
 * tax_id, founded) that the frontend has been sending all along.
 *
 * Idempotent and value-preserving: any value the super-admin manually
 * set under the old key is carried over to the new key.
 */
return new class extends Migration
{
    private const RENAMES = [
        'org_name'        => 'organization_name',
        'org_acronym'     => 'organization_acronym',
        'org_email'       => 'organization_email',
        'org_phone'       => 'organization_phone',
        'org_address'     => 'organization_address',
        'org_city'        => 'organization_city',
        'org_state'       => 'organization_state',
        'org_country'     => 'organization_country',
        'org_website'     => 'organization_website',
        'org_logo_url'    => 'organization_logo_url',
    ];

    private const NEW_KEYS = [
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
    ];

    public function up(): void
    {
        foreach (self::RENAMES as $oldKey => $newKey) {
            $newAlreadyExists = DB::table('system_settings')->where('key', $newKey)->exists();
            $oldExists        = DB::table('system_settings')->where('key', $oldKey)->exists();

            if (!$oldExists) {
                continue;
            }

            if ($newAlreadyExists) {
                // Both rows exist — preserve any value on the old row,
                // then drop it. Defensive against partial earlier runs.
                $oldValue = DB::table('system_settings')->where('key', $oldKey)->value('value');
                if ($oldValue !== null && $oldValue !== '') {
                    DB::table('system_settings')->where('key', $newKey)->update(['value' => $oldValue]);
                }
                DB::table('system_settings')->where('key', $oldKey)->delete();
            } else {
                DB::table('system_settings')->where('key', $oldKey)->update([
                    'key'        => $newKey,
                    'updated_at' => now(),
                ]);
            }
        }

        // Insert any missing new keys. We do an explicit exists-check + insert
        // (rather than updateOrInsert) so we can supply a UUID for the
        // primary key — system_settings.id has no DB default and Eloquent's
        // auto-UUID lives on the model, not the raw query builder.
        foreach (self::NEW_KEYS as $row) {
            $exists = DB::table('system_settings')->where('key', $row['key'])->exists();
            if (!$exists) {
                DB::table('system_settings')->insert(array_merge(
                    ['id' => (string) Str::uuid()],
                    $row,
                    [
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                ));
            }
        }
    }

    public function down(): void
    {
        // Drop the three new keys (only if untouched — value still null)
        foreach (self::NEW_KEYS as $row) {
            DB::table('system_settings')
                ->where('key', $row['key'])
                ->whereNull('value')
                ->delete();
        }

        // Restore old key names
        foreach (self::RENAMES as $oldKey => $newKey) {
            $oldExists = DB::table('system_settings')->where('key', $oldKey)->exists();
            if ($oldExists) {
                continue;
            }
            DB::table('system_settings')->where('key', $newKey)->update([
                'key'        => $oldKey,
                'updated_at' => now(),
            ]);
        }
    }
};
