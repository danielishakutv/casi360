<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    use HasUuids;

    protected $table = 'system_settings';

    protected $fillable = [
        'group',
        'key',
        'value',
        'type',
        'label',
        'description',
        'is_public',
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    /**
     * Return the value cast to its declared type.
     */
    public function getCastedValueAttribute(): mixed
    {
        return match ($this->type) {
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $this->value,
            'json'    => json_decode($this->value, true),
            default   => $this->value,
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeByGroup($query, string $group)
    {
        return $query->where('group', $group);
    }

    /*
    |--------------------------------------------------------------------------
    | Static Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Get a single setting value by key.
     */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();

        return $setting ? $setting->casted_value : $default;
    }

    /**
     * Set a single setting value by key.
     */
    public static function setValue(string $key, mixed $value): bool
    {
        $setting = static::where('key', $key)->first();

        if (!$setting) {
            return false;
        }

        if ($setting->type === 'json' && is_array($value)) {
            $value = json_encode($value);
        } elseif ($setting->type === 'boolean') {
            $value = $value ? '1' : '0';
        } else {
            $value = (string) $value;
        }

        return $setting->update(['value' => $value]);
    }

    /**
     * Get all settings grouped by group, returning cast values.
     */
    public static function allGrouped(): array
    {
        return static::orderBy('group')
            ->orderBy('key')
            ->get()
            ->groupBy('group')
            ->map(function ($settings) {
                return $settings->mapWithKeys(function ($setting) {
                    return [$setting->key => $setting->casted_value];
                });
            })
            ->toArray();
    }

    /*
    |--------------------------------------------------------------------------
    | API Serialization
    |--------------------------------------------------------------------------
    */

    public function toApiArray(): array
    {
        return [
            'id'          => $this->id,
            'group'       => $this->group,
            'key'         => $this->key,
            'value'       => $this->casted_value,
            'type'        => $this->type,
            'label'       => $this->label,
            'description' => $this->description,
            'is_public'   => $this->is_public,
        ];
    }
}
