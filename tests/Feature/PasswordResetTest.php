<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->markAsInstalled();
    }

    // ── Forgot password form ──────────────────────────────────────────────────

    public function test_forgot_password_page_renders_for_guests(): void
    {
        $this->get('/forgot-password')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Auth/ForgotPassword'));
    }

    public function test_authenticated_user_is_redirected_from_forgot_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/forgot-password')
            ->assertRedirect();
    }

    // ── Send reset link ───────────────────────────────────────────────────────

    public function test_reset_link_is_sent_for_existing_email(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email])
            ->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_reset_link_is_not_sent_for_unknown_email(): void
    {
        Notification::fake();

        $this->post('/forgot-password', ['email' => 'nobody@example.com'])
            ->assertSessionHasErrors('email');

        Notification::assertNothingSent();
    }

    public function test_forgot_password_validates_email_format(): void
    {
        $this->post('/forgot-password', ['email' => 'not-an-email'])
            ->assertSessionHasErrors('email');
    }

    public function test_forgot_password_requires_email_field(): void
    {
        $this->post('/forgot-password', [])
            ->assertSessionHasErrors('email');
    }

    // ── Reset password form ───────────────────────────────────────────────────

    public function test_reset_password_page_renders_with_token(): void
    {
        $this->get('/reset-password/some-token?email=user@example.com')
            ->assertOk()
            ->assertInertia(fn ($page) =>
                $page->component('Auth/ResetPassword')
                     ->where('token', 'some-token')
                     ->where('email', 'user@example.com')
            );
    }

    public function test_reset_password_page_accessible_to_guests(): void
    {
        $this->get('/reset-password/some-token')
            ->assertOk();
    }

    // ── Reset password ────────────────────────────────────────────────────────

    public function test_user_can_reset_password_with_valid_token(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        // Request a reset link to get a valid token
        $this->post('/forgot-password', ['email' => $user->email]);

        // Grab the token from the notification
        $token = null;
        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use (&$token) {
            $token = $notification->token;
            return true;
        });

        // Use the token to reset the password
        $this->post('/reset-password', [
            'token'                 => $token,
            'email'                 => $user->email,
            'password'              => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])->assertRedirect(route('login'));

        // Verify password was changed
        $this->assertTrue(Hash::check('newpassword123', $user->fresh()->password));
    }

    public function test_reset_fails_with_invalid_token(): void
    {
        $user = User::factory()->create();

        $this->post('/reset-password', [
            'token'                 => 'invalid-token',
            'email'                 => $user->email,
            'password'              => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])->assertSessionHasErrors('email');
    }

    public function test_reset_fails_with_mismatched_password_confirmation(): void
    {
        $this->post('/reset-password', [
            'token'                 => 'some-token',
            'email'                 => 'user@example.com',
            'password'              => 'newpassword123',
            'password_confirmation' => 'differentpassword',
        ])->assertSessionHasErrors('password');
    }

    public function test_reset_fails_when_password_is_too_short(): void
    {
        $this->post('/reset-password', [
            'token'                 => 'some-token',
            'email'                 => 'user@example.com',
            'password'              => 'short',
            'password_confirmation' => 'short',
        ])->assertSessionHasErrors('password');
    }

    public function test_reset_validates_required_fields(): void
    {
        $this->post('/reset-password', [])
            ->assertSessionHasErrors(['token', 'email', 'password']);
    }

    public function test_user_can_login_with_new_password_after_reset(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        $token = null;
        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use (&$token) {
            $token = $notification->token;
            return true;
        });

        $this->post('/reset-password', [
            'token'                 => $token,
            'email'                 => $user->email,
            'password'              => 'brandnewpass1',
            'password_confirmation' => 'brandnewpass1',
        ]);

        $this->post('/login', [
            'email'    => $user->email,
            'password' => 'brandnewpass1',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_reset_token_cannot_be_used_twice(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        $token = null;
        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use (&$token) {
            $token = $notification->token;
            return true;
        });

        // First reset — should succeed
        $this->post('/reset-password', [
            'token'                 => $token,
            'email'                 => $user->email,
            'password'              => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        // Second reset with same token — should fail
        $this->post('/reset-password', [
            'token'                 => $token,
            'email'                 => $user->email,
            'password'              => 'anotherpassword',
            'password_confirmation' => 'anotherpassword',
        ])->assertSessionHasErrors('email');
    }
}
