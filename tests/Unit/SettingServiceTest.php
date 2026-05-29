<?php

namespace Tests\Unit;

use App\Models\Setting;
use App\Services\SettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SettingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->markAsInstalled();
        Cache::flush();
    }

    public function test_get_returns_string_value(): void
    {
        Setting::create(['group' => 'site', 'key' => 'site.name', 'value' => 'My CMS', 'type' => 'string']);

        $this->assertSame('My CMS', Setting::get('site.name'));
    }

    public function test_get_casts_integer_type(): void
    {
        Setting::create(['group' => 'media', 'key' => 'media.max_upload_mb', 'value' => '42', 'type' => 'integer']);

        $result = Setting::get('media.max_upload_mb');
        $this->assertSame(42, $result);
        $this->assertIsInt($result);
    }

    public function test_get_casts_boolean_type(): void
    {
        Setting::create(['group' => 'site', 'key' => 'site.debug', 'value' => '1', 'type' => 'boolean']);

        $this->assertTrue(Setting::get('site.debug'));
    }

    public function test_get_returns_fallback_when_key_missing(): void
    {
        $this->assertSame('default', Setting::get('nonexistent.key', 'default'));
    }

    public function test_set_persists_value(): void
    {
        Setting::create(['group' => 'site', 'key' => 'site.name', 'value' => 'Old', 'type' => 'string']);

        Setting::set('site.name', 'New');

        $this->assertDatabaseHas('settings', ['key' => 'site.name', 'value' => 'New']);
    }

    public function test_set_busts_cache(): void
    {
        Setting::create(['group' => 'site', 'key' => 'site.name', 'value' => 'Cached', 'type' => 'string']);

        // Prime the cache
        $service = new SettingService;
        $service->all();
        $this->assertTrue(Cache::has(SettingService::CACHE_KEY));

        // set() should bust it
        Setting::set('site.name', 'New');
        $this->assertFalse(Cache::has(SettingService::CACHE_KEY));
    }

    public function test_all_uses_cache(): void
    {
        Setting::create(['group' => 'site', 'key' => 'site.name', 'value' => 'Test', 'type' => 'string']);

        $service = new SettingService;
        $service->all(); // primes cache

        $this->assertTrue(Cache::has(SettingService::CACHE_KEY));
    }

    public function test_get_casts_boolean_false(): void
    {
        Setting::create(['group' => 'site', 'key' => 'site.maintenance', 'value' => '0', 'type' => 'boolean']);

        $this->assertFalse(Setting::get('site.maintenance'));
    }

    public function test_set_throws_for_unknown_key(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Setting::set('nonexistent.key', 'value');
    }

    public function test_set_does_not_throw_when_value_unchanged(): void
    {
        Setting::create(['group' => 'site', 'key' => 'site.name', 'value' => 'Same', 'type' => 'string']);

        // Should not throw even though the value is the same
        Setting::set('site.name', 'Same');

        $this->assertDatabaseHas('settings', ['key' => 'site.name', 'value' => 'Same']);
    }
}
