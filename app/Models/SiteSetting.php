<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $fillable = ['key', 'value'];

    private const TRUE_VALUES = ['1', 'true', 'yes', 'on'];

    /**
     * Get a setting value by key, with optional default.
     */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();

        return $setting ? $setting->value : $default;
    }

    /**
     * Set (upsert) a single setting value.
     */
    public static function setValue(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    public static function booleanValue(string $key, bool $default = false): bool
    {
        $value = static::getValue($key);

        if ($value === null) {
            return $default;
        }

        return in_array(strtolower((string) $value), self::TRUE_VALUES, true);
    }

    /**
     * Return all settings as a key → value array.
     *
     * @return array<string, string|null>
     */
    public static function allAsMap(): array
    {
        return static::all()->pluck('value', 'key')->toArray();
    }
}

