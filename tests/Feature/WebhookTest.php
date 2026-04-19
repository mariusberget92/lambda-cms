<?php

namespace Tests\Feature;

use App\Jobs\DispatchWebhookJob;
use App\Models\Page;
use App\Models\Post;
use App\Models\User;
use App\Models\Webhook;
use App\Observers\PageObserver;
use App\Observers\PostObserver;
use App\Services\WebhookService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->markAsInstalled();
        $this->seedRolesAndPermissions();
    }

    private function makeAdmin(): User
    {
        return User::factory()->create()->assignRole('administrator');
    }

    private function makeUser(): User
    {
        return User::factory()->create()->assignRole('user');
    }

    private function webhookPayload(array $overrides = []): array
    {
        return array_merge([
            'url'       => 'https://example.com/hook',
            'secret'    => null,
            'events'    => ['post.published'],
            'is_active' => true,
        ], $overrides);
    }

    // ── Access control ────────────────────────────────────────────────────────

    public function test_guest_cannot_access_webhooks_index(): void
    {
        $this->get('/webhooks')->assertRedirect('/login');
    }

    public function test_non_admin_cannot_access_webhooks_index(): void
    {
        $this->actingAs($this->makeUser())
            ->get('/webhooks')
            ->assertRedirect(route('dashboard'));
    }

    public function test_admin_can_access_webhooks_index(): void
    {
        $this->actingAs($this->makeAdmin())
            ->get('/webhooks')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Settings/Webhooks'));
    }

    public function test_webhooks_index_lists_existing_webhooks(): void
    {
        Webhook::create($this->webhookPayload(['url' => 'https://example.com/my-hook']));

        $this->actingAs($this->makeAdmin())
            ->get('/webhooks')
            ->assertInertia(fn ($page) =>
                $page->has('webhooks', 1)
                     ->where('webhooks.0.url', 'https://example.com/my-hook')
            );
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function test_admin_can_create_webhook(): void
    {
        $this->actingAs($this->makeAdmin())
            ->post('/webhooks', $this->webhookPayload())
            ->assertRedirect();

        $this->assertDatabaseHas('webhooks', ['url' => 'https://example.com/hook']);
    }

    public function test_webhook_is_active_by_default(): void
    {
        $this->actingAs($this->makeAdmin())
            ->post('/webhooks', $this->webhookPayload());

        $this->assertTrue(Webhook::first()->is_active);
    }

    public function test_create_validates_url_is_required(): void
    {
        $this->actingAs($this->makeAdmin())
            ->post('/webhooks', $this->webhookPayload(['url' => '']))
            ->assertSessionHasErrors('url');
    }

    public function test_create_validates_url_format(): void
    {
        $this->actingAs($this->makeAdmin())
            ->post('/webhooks', $this->webhookPayload(['url' => 'not-a-url']))
            ->assertSessionHasErrors('url');
    }

    public function test_create_validates_events_is_required(): void
    {
        $this->actingAs($this->makeAdmin())
            ->post('/webhooks', $this->webhookPayload(['events' => []]))
            ->assertSessionHasErrors('events');
    }

    public function test_create_validates_events_must_be_known_values(): void
    {
        $this->actingAs($this->makeAdmin())
            ->post('/webhooks', $this->webhookPayload(['events' => ['unknown.event']]))
            ->assertSessionHasErrors('events.0');
    }

    public function test_create_accepts_all_valid_event_types(): void
    {
        $validEvents = ['post.published', 'post.updated', 'post.deleted', 'page.published', 'page.updated', 'page.deleted'];

        $this->actingAs($this->makeAdmin())
            ->post('/webhooks', $this->webhookPayload(['events' => $validEvents]))
            ->assertRedirect();

        $this->assertDatabaseHas('webhooks', ['url' => 'https://example.com/hook']);
    }

    public function test_non_admin_cannot_create_webhook(): void
    {
        $this->actingAs($this->makeUser())
            ->post('/webhooks', $this->webhookPayload())
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseMissing('webhooks', ['url' => 'https://example.com/hook']);
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function test_admin_can_update_webhook(): void
    {
        $webhook = Webhook::create($this->webhookPayload());

        $this->actingAs($this->makeAdmin())
            ->put("/webhooks/{$webhook->id}", $this->webhookPayload([
                'url'    => 'https://updated.example.com/hook',
                'events' => ['post.deleted'],
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('webhooks', [
            'id'  => $webhook->id,
            'url' => 'https://updated.example.com/hook',
        ]);
    }

    public function test_update_validates_url(): void
    {
        $webhook = Webhook::create($this->webhookPayload());

        $this->actingAs($this->makeAdmin())
            ->put("/webhooks/{$webhook->id}", $this->webhookPayload(['url' => 'bad-url']))
            ->assertSessionHasErrors('url');
    }

    public function test_non_admin_cannot_update_webhook(): void
    {
        $webhook = Webhook::create($this->webhookPayload());

        $this->actingAs($this->makeUser())
            ->put("/webhooks/{$webhook->id}", $this->webhookPayload(['url' => 'https://other.example.com']))
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseMissing('webhooks', ['url' => 'https://other.example.com']);
    }

    // ── Destroy ───────────────────────────────────────────────────────────────

    public function test_admin_can_delete_webhook(): void
    {
        $webhook = Webhook::create($this->webhookPayload());

        $this->actingAs($this->makeAdmin())
            ->delete("/webhooks/{$webhook->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('webhooks', ['id' => $webhook->id]);
    }

    public function test_non_admin_cannot_delete_webhook(): void
    {
        $webhook = Webhook::create($this->webhookPayload());

        $this->actingAs($this->makeUser())
            ->delete("/webhooks/{$webhook->id}")
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('webhooks', ['id' => $webhook->id]);
    }

    // ── PostObserver dispatching ──────────────────────────────────────────────

    public function test_post_observer_dispatches_post_published_when_created_as_published(): void
    {
        Queue::fake();

        Webhook::create($this->webhookPayload(['events' => ['post.published']]));

        $user = $this->makeUser();
        Post::factory()->published()->create(['user_id' => $user->id]);

        Queue::assertPushed(DispatchWebhookJob::class);
    }

    public function test_post_observer_dispatches_post_published_when_status_changes_to_published(): void
    {
        Queue::fake();

        Webhook::create($this->webhookPayload(['events' => ['post.published']]));

        $user = $this->makeUser();
        $post = Post::factory()->draft()->create(['user_id' => $user->id]);

        Queue::assertNotPushed(DispatchWebhookJob::class);

        $post->update(['status' => 'published', 'published_at' => now()]);

        Queue::assertPushed(DispatchWebhookJob::class);
    }

    public function test_post_observer_dispatches_post_updated_for_already_published_post(): void
    {
        Queue::fake();

        Webhook::create($this->webhookPayload(['events' => ['post.updated']]));

        $user = $this->makeUser();
        $post = Post::factory()->published()->create(['user_id' => $user->id]);

        Queue::assertNotPushed(DispatchWebhookJob::class); // publishing fires post.published, not post.updated

        Queue::fake(); // reset

        $post->update(['title' => 'Updated Title']);

        Queue::assertPushed(DispatchWebhookJob::class);
    }

    public function test_post_observer_dispatches_post_deleted(): void
    {
        Queue::fake();

        Webhook::create($this->webhookPayload(['events' => ['post.deleted']]));

        $user = $this->makeUser();
        $post = Post::factory()->published()->create(['user_id' => $user->id]);

        Queue::fake(); // reset after creation

        $post->delete();

        Queue::assertPushed(DispatchWebhookJob::class);
    }

    public function test_post_observer_does_not_dispatch_when_no_matching_webhook(): void
    {
        Queue::fake();

        // Webhook only listens for page events, not post events
        Webhook::create($this->webhookPayload(['events' => ['page.published']]));

        $user = $this->makeUser();
        Post::factory()->published()->create(['user_id' => $user->id]);

        Queue::assertNotPushed(DispatchWebhookJob::class);
    }

    public function test_post_observer_does_not_dispatch_when_webhook_is_inactive(): void
    {
        Queue::fake();

        Webhook::create($this->webhookPayload([
            'events'    => ['post.published'],
            'is_active' => false,
        ]));

        $user = $this->makeUser();
        Post::factory()->published()->create(['user_id' => $user->id]);

        Queue::assertNotPushed(DispatchWebhookJob::class);
    }

    // ── PageObserver dispatching ──────────────────────────────────────────────

    public function test_page_observer_dispatches_page_published_when_created_as_published(): void
    {
        Queue::fake();

        Webhook::create($this->webhookPayload(['events' => ['page.published']]));

        Page::factory()->published()->create();

        Queue::assertPushed(DispatchWebhookJob::class);
    }

    public function test_page_observer_dispatches_page_published_when_status_changes_to_published(): void
    {
        Queue::fake();

        Webhook::create($this->webhookPayload(['events' => ['page.published']]));

        $page = Page::factory()->draft()->create();

        Queue::assertNotPushed(DispatchWebhookJob::class);

        $page->update(['status' => 'published']);

        Queue::assertPushed(DispatchWebhookJob::class);
    }

    public function test_page_observer_dispatches_page_deleted(): void
    {
        Queue::fake();

        Webhook::create($this->webhookPayload(['events' => ['page.deleted']]));

        $page = Page::factory()->published()->create();

        Queue::fake(); // reset after creation

        $page->delete();

        Queue::assertPushed(DispatchWebhookJob::class);
    }

    // ── WebhookService unit ───────────────────────────────────────────────────

    public function test_webhook_service_dispatches_job_for_matching_active_webhook(): void
    {
        Queue::fake();

        Webhook::create($this->webhookPayload(['events' => ['post.published']]));

        app(WebhookService::class)->dispatch('post.published', ['id' => 1]);

        Queue::assertPushed(DispatchWebhookJob::class);
    }

    public function test_webhook_service_does_not_dispatch_for_inactive_webhook(): void
    {
        Queue::fake();

        Webhook::create($this->webhookPayload(['events' => ['post.published'], 'is_active' => false]));

        app(WebhookService::class)->dispatch('post.published', ['id' => 1]);

        Queue::assertNotPushed(DispatchWebhookJob::class);
    }

    public function test_webhook_service_dispatches_job_for_each_matching_webhook(): void
    {
        Queue::fake();

        Webhook::create($this->webhookPayload(['url' => 'https://one.example.com', 'events' => ['post.published']]));
        Webhook::create($this->webhookPayload(['url' => 'https://two.example.com', 'events' => ['post.published']]));
        Webhook::create($this->webhookPayload(['url' => 'https://three.example.com', 'events' => ['page.published']])); // different event

        app(WebhookService::class)->dispatch('post.published', ['id' => 1]);

        Queue::assertPushed(DispatchWebhookJob::class, 2);
    }
}
