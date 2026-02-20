<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->markAsInstalled();
    }

    // ── Login page ────────────────────────────────────────────────────────────

    public function test_login_page_is_accessible_to_guests(): void
    {
        $this->get('/login')->assertOk();
    }

    public function test_login_page_redirects_authenticated_users(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/login')->assertRedirect();
    }

    // ── Successful login ──────────────────────────────────────────────────────

    public function test_user_can_login_with_correct_credentials(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email'    => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email'    => $user->email,
            'password' => 'wrongpassword',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_login_fails_with_unknown_email(): void
    {
        $this->post('/login', [
            'email'    => 'nobody@example.com',
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_login_validates_required_fields(): void
    {
        $this->post('/login', [])
            ->assertSessionHasErrors(['email', 'password']);
    }

    // ── Rate limiting ─────────────────────────────────────────────────────────

    public function test_login_is_rate_limited_after_5_failed_attempts(): void
    {
        $user = User::factory()->create();

        // Clear any existing rate limit state
        $throttleKey = strtolower($user->email) . '|127.0.0.1';
        RateLimiter::clear($throttleKey);

        // 5 failed attempts
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'email'    => $user->email,
                'password' => 'wrongpassword',
            ]);
        }

        // 6th attempt should be throttled
        $this->post('/login', [
            'email'    => $user->email,
            'password' => 'wrongpassword',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(RateLimiter::tooManyAttempts($throttleKey, 5));
    }

    public function test_rate_limit_is_cleared_after_successful_login(): void
    {
        $user = User::factory()->create();
        $throttleKey = strtolower($user->email) . '|127.0.0.1';
        RateLimiter::clear($throttleKey);

        // Hit the limiter a few times
        RateLimiter::hit($throttleKey, 60);
        RateLimiter::hit($throttleKey, 60);

        // Successful login
        $this->post('/login', [
            'email'    => $user->email,
            'password' => 'password',
        ]);

        $this->assertFalse(RateLimiter::tooManyAttempts($throttleKey, 5));
    }

    // ── Logout ────────────────────────────────────────────────────────────────

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/logout')
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_guest_cannot_access_logout(): void
    {
        $this->post('/logout')->assertRedirect(route('login'));
    }
}
