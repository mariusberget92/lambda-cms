<?php

namespace App\Models;

use App\Services\SettingService;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['group', 'key', 'value', 'type'];

    /**
     * Retrieve a setting value by key, casting to the correct type.
     */
    public static function get(string $key, mixed $fallback = null): mixed
    {
        $service  = app(SettingService::class);
        $settings = $service->all();

        if (! $settings->has($key)) {
            return $fallback;
        }

        $setting = $settings->get($key);

        return match ($setting->type) {
            'integer' => (int) $setting->value,
            'boolean' => (bool) $setting->value,
            default   => (string) ($setting->value ?? ''),
        };
    }

    /**
     * Persist a setting value and bust the cache.
     */
    public static function set(string $key, mixed $value): void
    {
        static::where('key', $key)->update(['value' => (string) $value]);
        app(SettingService::class)->bust();
    }
}
