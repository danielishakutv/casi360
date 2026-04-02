<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\SystemSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SystemSettingsController extends Controller
{
    /**
     * GET /api/v1/settings/general/public
     *
     * Returns public settings (org name, logo, etc.) — no auth required.
     */
    public function publicSettings(): JsonResponse
    {
        $settings = SystemSetting::public()
            ->orderBy('group')
            ->orderBy('key')
            ->get();

        $grouped = $settings->groupBy('group')->map(function ($items) {
            return $items->mapWithKeys(fn($s) => [$s->key => $s->casted_value]);
        });

        return $this->success([
            'settings' => $grouped,
        ]);
    }

    /**
     * GET /api/v1/settings/general
     *
     * Returns all settings grouped, with full metadata.
     * Super admin only.
     */
    public function index(): JsonResponse
    {
        $settings = SystemSetting::orderBy('group')
            ->orderBy('key')
            ->get();

        $grouped = $settings->groupBy('group')->map(function ($items) {
            return $items->map(fn($s) => $s->toApiArray())->values();
        });

        return $this->success([
            'settings' => $grouped,
        ]);
    }

    /**
     * GET /api/v1/settings/general/{key}
     *
     * Returns a single setting by key.
     * Super admin only.
     */
    public function show(string $key): JsonResponse
    {
        $setting = SystemSetting::where('key', $key)->first();

        if (!$setting) {
            return $this->error('Setting not found.', 404);
        }

        return $this->success([
            'setting' => $setting->toApiArray(),
        ]);
    }

    /**
     * PATCH /api/v1/settings/general/{key}
     *
     * Update a single setting value.
     * Super admin only.
     */
    public function update(Request $request, string $key): JsonResponse
    {
        $setting = SystemSetting::where('key', $key)->first();

        if (!$setting) {
            return $this->error('Setting not found.', 404);
        }

        $request->validate([
            'value' => 'present',
        ]);

        $oldValue = $setting->value;
        $newValue = $request->input('value');

        // Cast to storage format
        if ($setting->type === 'json' && is_array($newValue)) {
            $newValue = json_encode($newValue);
        } elseif ($setting->type === 'boolean') {
            $newValue = filter_var($newValue, FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
        } elseif ($setting->type === 'integer') {
            if (!is_numeric($newValue)) {
                return $this->error('Value must be a number for integer settings.', 422);
            }
            $newValue = (string) (int) $newValue;
        } else {
            $newValue = $newValue === null ? null : (string) $newValue;
        }

        return DB::transaction(function () use ($setting, $oldValue, $newValue) {
            $setting->update(['value' => $newValue]);

            AuditLog::record(
                'system_setting_updated',
                'system_settings',
                $setting->id,
                ['key' => $setting->key, 'old_value' => $oldValue],
                ['key' => $setting->key, 'new_value' => $newValue]
            );

            return $this->success([
                'setting' => $setting->fresh()->toApiArray(),
            ], 'Setting updated successfully.');
        });
    }

    /**
     * PATCH /api/v1/settings/general/bulk
     *
     * Bulk update multiple settings at once.
     * Expects: { "settings": { "key1": "value1", "key2": "value2" } }
     * Super admin only.
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        $request->validate([
            'settings' => 'required|array|min:1',
        ]);

        $input = $request->input('settings');
        $keys = array_keys($input);

        $settings = SystemSetting::whereIn('key', $keys)->get()->keyBy('key');

        $notFound = array_diff($keys, $settings->keys()->toArray());
        if (count($notFound) > 0) {
            return $this->error(
                'Some settings were not found: ' . implode(', ', $notFound),
                422
            );
        }

        return DB::transaction(function () use ($settings, $input) {
            $updated = [];

            foreach ($input as $key => $newValue) {
                $setting = $settings[$key];
                $oldValue = $setting->value;

                // Cast to storage format
                if ($setting->type === 'json' && is_array($newValue)) {
                    $storedValue = json_encode($newValue);
                } elseif ($setting->type === 'boolean') {
                    $storedValue = filter_var($newValue, FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
                } elseif ($setting->type === 'integer') {
                    $storedValue = (string) (int) $newValue;
                } else {
                    $storedValue = $newValue === null ? null : (string) $newValue;
                }

                $setting->update(['value' => $storedValue]);

                AuditLog::record(
                    'system_setting_updated',
                    'system_settings',
                    $setting->id,
                    ['key' => $key, 'old_value' => $oldValue],
                    ['key' => $key, 'new_value' => $storedValue]
                );

                $updated[] = $key;
            }

            return $this->success([
                'updated' => $updated,
                'count' => count($updated),
            ], count($updated) . ' setting(s) updated successfully.');
        });
    }
}
