<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstallMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    // ── EnsureInstalled ───────────────────────────────────────────────────────

    public function test_home_redirects_to_install_when_not_installed(): void
    {
        $this->markAsNotInstalled();

        $this->get('/')->assertRedirect('/install');
    }

    public function test_login_redirects_to_install_when_not_installed(): void
    {
        $this->markAsNotInstalled();

        $this->get('/login')->assertRedirect('/install');
    }

    public function test_dashboard_redirects_to_install_when_not_installed(): void
    {
        $this->markAsNotInstalled();
        $this->seedRolesAndPermissions();

        // Must be authenticated so the auth middleware doesn't redirect first
        $user = User::factory()->create()->assignRole('user');

        $this->actingAs($user)->get('/dashboard')->assertRedirect('/install');
    }

    // ── EnsureNotInstalled ────────────────────────────────────────────────────

    public function test_install_database_redirects_to_home_when_already_installed(): void
    {
        $this->markAsInstalled();

        $this->get('/install/database')->assertRedirect('/');
    }

    public function test_install_site_redirects_to_home_when_already_installed(): void
    {
        $this->markAsInstalled();

        $this->get('/install/site')->assertRedirect('/');
    }

    public function test_install_admin_redirects_to_home_when_already_installed(): void
    {
        $this->markAsInstalled();

        $this->get('/install/admin')->assertRedirect('/');
    }

    public function test_install_mail_redirects_to_home_when_already_installed(): void
    {
        $this->markAsInstalled();

        $this->get('/install/mail')->assertRedirect('/');
    }

    // ── Install routes accessible when not installed ──────────────────────────

    public function test_install_database_accessible_when_not_installed(): void
    {
        $this->markAsNotInstalled();

        $this->get('/install/database')->assertOk();
    }

    public function test_install_redirects_to_database_step(): void
    {
        $this->markAsNotInstalled();

        $this->get('/install')->assertRedirect('/install/database');
    }

    // ── Install step POST validations ─────────────────────────────────────────

    public function test_install_site_validates_required_fields(): void
    {
        $this->markAsNotInstalled();

        $this->post('/install/site', [])
            ->assertSessionHasErrors(['site_name', 'site_url']);
    }

    public function test_install_admin_validates_required_fields(): void
    {
        $this->markAsNotInstalled();

        $this->post('/install/admin', [])
            ->assertSessionHasErrors(['name', 'email', 'password']);
    }

    public function test_install_admin_validates_password_confirmation(): void
    {
        $this->markAsNotInstalled();

        $this->post('/install/admin', [
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'doesnotmatch',
        ])->assertSessionHasErrors(['password']);
    }

    public function test_install_admin_stores_data_in_session_and_redirects(): void
    {
        $this->markAsNotInstalled();

        $this->post('/install/admin', [
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect('/install/mail');
    }
}
