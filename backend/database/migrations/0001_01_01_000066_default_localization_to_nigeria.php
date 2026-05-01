<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Switches the default localization values to West Africa / Nigeria, since
 * CASI360 deployments are Nigerian-first. Updates the row values only when
 * they still hold the previous US-centric defaults — so any super-admin
 * override already in place is preserved.
 *
 * Idempotent: re-running is a no-op once the values have been switched.
 */
return new class extends Migration
{
    /**
     * key => [old default, new default].
     * Each row is only updated when its current value still equals the
     * old default (or is null). Anything else is left alone.
     */
    private const SWITCHES = [
        'timezone'        => ['America/New_York', 'Africa/Lagos'],
        'date_format'     => ['MM/DD/YYYY',       'DD/MM/YYYY'],
        'currency'        => ['USD',              'NGN'],
        'currency_symbol' => ['$',                '₦'],
    ];

    public function up(): void
    {
        foreach (self::SWITCHES as $key => [$oldDefault, $newDefault]) {
            DB::table('system_settings')
                ->where('key', $key)
                ->where(function ($q) use ($oldDefault) {
                    $q->where('value', $oldDefault)
                      ->orWhereNull('value')
                      ->orWhere('value', '');
                })
                ->update([
                    'value'      => $newDefault,
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        foreach (self::SWITCHES as $key => [$oldDefault, $newDefault]) {
            DB::table('system_settings')
                ->where('key', $key)
                ->where('value', $newDefault)
                ->update([
                    'value'      => $oldDefault,
                    'updated_at' => now(),
                ]);
        }
    }
};
