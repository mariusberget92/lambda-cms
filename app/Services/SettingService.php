<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class SettingService
{
    private const CACHE_KEY = 'settings.all';
    private const CACHE_TTL = 3600; // 1 hour

    public function all(): Collection
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return \App\Models\Setting::all()->keyBy('key');
        });
    }

    public function bust(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
