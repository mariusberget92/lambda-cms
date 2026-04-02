<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->markAsInstalled();
        $this->seedRolesAndPermissions();
        $this->seedSettings();
    }

    private function seedSettings(): void
    {
        $defaults = [
            ['group' => 'site',   'key' => 'site.name',              'value' => 'Lambda CMS',        'type' => 'string'],
            ['group' => 'site',   'key' => 'site.url',               'value' => 'http://localhost',  'type' => 'string'],
            ['group' => 'site',   'key' => 'site.accent_color',      'value' => '',                  'type' => 'string'],
            ['group' => 'locale', 'key' => 'locale.timezone',        'value' => 'UTC',               'type' => 'string'],
            ['group' => 'locale', 'key' => 'locale.date_format',     'value' => 'Y-m-d',             'type' => 'string'],
            ['group' => 'media',  'key' => 'media.max_upload_mb',    'value' => '10',                'type' => 'integer'],
            ['group' => 'media',  'key' => 'media.resize_max_width', 'value' => '1920',              'type' => 'integer'],
            ['group' => 'mail',   'key' => 'mail.driver',            'value' => 'log',               'type' => 'string'],
            ['group' => 'mail',   'key' => 'mail.host',              'value' => '',                  'type' => 'string'],
            ['group' => 'mail',   'key' => 'mail.port',              'value' => '587',               'type' => 'integer'],
            ['group' => 'mail',   'key' => 'mail.username',          'value' => '',                  'type' => 'string'],
            ['group' => 'mail',   'key' => 'mail.password',          'value' => '',                  'type' => 'string'],
            ['group' => 'mail',   'key' => 'mail.from_address',      'value' => 'noreply@example.com', 'type' => 'string'],
            ['group' => 'mail',   'key' => 'mail.from_name',         'value' => 'Lambda CMS',        'type' => 'string'],
            ['group' => 'mail',   'key' => 'mail.encryption',        'value' => 'tls',               'type' => 'string'],
            ['group' => 'seo',    'key' => 'seo.title_separator',      'value' => ' | ',               'type' => 'string'],
            ['group' => 'seo',    'key' => 'seo.default_description',   'value' => '',                  'type' => 'string'],
            ['group' => 'seo',    'key' => 'seo.default_og_image_url',  'value' => '',                  'type' => 'string'],
            ['group' => 'seo',    'key' => 'seo.default_keywords',      'value' => '',                  'type' => 'string'],
        ];

        foreach ($defaults as $row) {
            Setting::create($row);
        }
    }

    private function makeAdmin(): User
    {
        return User::factory()->create()->assignRole('administrator');
    }

    private function makeUser(): User
    {
        return User::factory()->create()->assignRole('user');
    }

    // ── Access control ────────────────────────────────────────────────────────

    public function test_guest_cannot_access_settings(): void
    {
        $this->get('/settings')->assertRedirect('/login');
    }

    public function test_regular_user_cannot_access_settings(): void
    {
        $this->actingAs($this->makeUser())->get('/settings')->assertRedirect(route('dashboard'));
    }

    public function test_administrator_can_access_settings(): void
    {
        $this->actingAs($this->makeAdmin())->get('/settings')->assertOk();
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function test_administrator_can_update_site_settings(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->put('/settings/site', [
            'site.name' => 'New Site Name',
            'site.url'  => 'https://example.com',
        ])->assertRedirect();

        $this->assertDatabaseHas('settings', ['key' => 'site.name', 'value' => 'New Site Name']);
    }

    public function test_site_settings_validation_rejects_invalid_url(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->put('/settings/site', [
            'site.name' => 'Test',
            'site.url'  => 'not-a-url',
        ])->assertSessionHasErrors('site.url');
    }

    public function test_administrator_can_update_media_settings(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->put('/settings/media', [
            'media.max_upload_mb'    => 25,
            'media.resize_max_width' => 2560,
        ])->assertRedirect();

        $this->assertDatabaseHas('settings', ['key' => 'media.max_upload_mb', 'value' => '25']);
    }

    public function test_media_settings_validation_rejects_out_of_range(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->put('/settings/media', [
            'media.max_upload_mb'    => 999,
            'media.resize_max_width' => 1920,
        ])->assertSessionHasErrors('media.max_upload_mb');
    }

    public function test_regular_user_cannot_update_settings(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->put('/settings/site', [
            'site.name' => 'Hacked',
            'site.url'  => 'https://hacked.com',
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseMissing('settings', ['key' => 'site.name', 'value' => 'Hacked']);
    }

    // ── Test email ────────────────────────────────────────────────────────────

    public function test_administrator_can_send_test_email(): void
    {
        Mail::fake();
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->post('/settings/test-email')->assertRedirect();

        Mail::assertSent(\App\Mail\TestMail::class);
    }

    public function test_regular_user_cannot_send_test_email(): void
    {
        Mail::fake();
        $user = $this->makeUser();

        $this->actingAs($user)->post('/settings/test-email')->assertRedirect(route('dashboard'));

        Mail::assertNothingSent();
    }
    public function test_administrator_can_update_seo_settings(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->put('/settings/seo', [
            'seo.title_separator'      => '|',
            'seo.default_description'  => 'My site about things',
            'seo.default_og_image_url' => 'https://example.com/og.jpg',
        ])->assertRedirect();

        $this->assertDatabaseHas('settings', [
            'key'   => 'seo.title_separator',
            'value' => '|',
        ]);
        $this->assertDatabaseHas('settings', [
            'key'   => 'seo.default_description',
            'value' => 'My site about things',
        ]);
        $this->assertDatabaseHas('settings', [
            'key'   => 'seo.default_og_image_url',
            'value' => 'https://example.com/og.jpg',
        ]);
    }

    public function test_regular_user_cannot_update_seo_settings(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->put('/settings/seo', [
            'seo.title_separator'      => '–',
            'seo.default_description'  => 'Hacked',
            'seo.default_og_image_url' => 'https://evil.com/img.jpg',
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseMissing('settings', ['key' => 'seo.default_description', 'value' => 'Hacked']);
    }

    public function test_admin_can_save_seo_default_keywords(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->put('/settings/seo', [
            'seo.title_separator'      => ' | ',
            'seo.default_description'  => '',
            'seo.default_og_image_url' => '',
            'seo.default_keywords'     => 'laravel, cms',
        ])->assertRedirect();

        $this->assertDatabaseHas('settings', [
            'key'   => 'seo.default_keywords',
            'value' => 'laravel, cms',
        ]);
    }

    public function test_admin_can_save_accent_color(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin)
            ->put('/settings/appearance', ['site.accent_color' => '#a3be8c'])
            ->assertRedirect();

        $this->assertDatabaseHas('settings', [
            'key'   => 'site.accent_color',
            'value' => '#a3be8c',
        ]);
    }

    public function test_accent_color_is_shared_as_inertia_prop(): void
    {
        Setting::set('site.accent_color', '#a3be8c');

        $admin = $this->makeAdmin();
        $response = $this->actingAs($admin)->get('/dashboard');
        $response->assertInertia(fn ($page) =>
            $page->where('accentColor', '#a3be8c')
        );
    }
}
