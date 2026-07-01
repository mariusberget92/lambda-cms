<?php

namespace Tests\Feature;

use App\Models\Subscriber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscribeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->markAsInstalled();
        $this->seedRolesAndPermissions();
    }

    // ── Public Subscribe ─────────────────────────────────────────────────────

    public function test_visitor_can_subscribe(): void
    {
        $this->post('/subscribe', [
            'email' => 'new@example.com',
            'name' => 'New User',
        ])->assertRedirect();

        $this->assertDatabaseHas('subscribers', [
            'email' => 'new@example.com',
            'name' => 'New User',
            'status' => 'active',
        ]);
    }

    public function test_subscribe_requires_valid_email(): void
    {
        $this->post('/subscribe', ['email' => 'not-an-email'])
            ->assertSessionHasErrors('email');
    }

    public function test_subscribe_requires_email(): void
    {
        $this->post('/subscribe', [])
            ->assertSessionHasErrors('email');
    }

    public function test_already_subscribed_user_gets_message(): void
    {
        Subscriber::create(['email' => 'existing@test.com', 'status' => 'active', 'subscribed_at' => now()]);

        $this->post('/subscribe', ['email' => 'existing@test.com'])
            ->assertSessionHas('subscribe_status', 'You are already subscribed!');
    }

    public function test_unsubscribed_user_can_resubscribe(): void
    {
        $sub = Subscriber::create([
            'email' => 'unsub@test.com',
            'status' => 'unsubscribed',
            'subscribed_at' => now(),
            'unsubscribed_at' => now(),
        ]);

        $this->post('/subscribe', ['email' => 'unsub@test.com', 'name' => 'Returning'])
            ->assertSessionHas('subscribe_status', 'Welcome back! You have been re-subscribed.');

        $sub->refresh();
        $this->assertEquals('active', $sub->status);
        $this->assertEquals('Returning', $sub->name);
        $this->assertNull($sub->unsubscribed_at);
    }

    // ── Public Unsubscribe ───────────────────────────────────────────────────

    public function test_subscriber_can_unsubscribe_via_token(): void
    {
        $sub = Subscriber::create([
            'email' => 'bye@test.com',
            'status' => 'active',
            'subscribed_at' => now(),
        ]);

        $this->get("/unsubscribe/{$sub->token}")
            ->assertOk()
            ->assertViewIs('unsubscribe');

        $sub->refresh();
        $this->assertEquals('unsubscribed', $sub->status);
        $this->assertNotNull($sub->unsubscribed_at);
    }

    public function test_unsubscribe_with_invalid_token_returns_404(): void
    {
        $this->get('/unsubscribe/invalidtoken123')->assertNotFound();
    }

    public function test_already_unsubscribed_user_stays_unsubscribed(): void
    {
        $sub = Subscriber::create([
            'email' => 'already@test.com',
            'status' => 'unsubscribed',
            'subscribed_at' => now(),
            'unsubscribed_at' => now()->subDay(),
        ]);

        $originalDate = $sub->unsubscribed_at->toDateTimeString();

        $this->get("/unsubscribe/{$sub->token}")->assertOk();

        $sub->refresh();
        $this->assertEquals('unsubscribed', $sub->status);
        $this->assertEquals($originalDate, $sub->unsubscribed_at->toDateTimeString());
    }

    // ── Subscribe form page ──────────────────────────────────────────────────

    public function test_subscribe_form_page_is_accessible(): void
    {
        $this->get('/subscribe')->assertOk();
    }
}
