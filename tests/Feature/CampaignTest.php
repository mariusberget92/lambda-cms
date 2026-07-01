<?php

namespace Tests\Feature;

use App\Jobs\SendCampaignJob;
use App\Models\Campaign;
use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CampaignTest extends TestCase
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

    private function makeUserWithPermission(): User
    {
        $user = User::factory()->create()->assignRole('user');
        $user->givePermissionTo('manage email');

        return $user;
    }

    private function makeUserWithout(): User
    {
        return User::factory()->create()->assignRole('user');
    }

    private function createCampaign(User $user, array $overrides = []): Campaign
    {
        return Campaign::create(array_merge([
            'user_id' => $user->id,
            'name' => 'Test Campaign',
            'subject' => 'Test Subject',
            'body' => '<p>Test body</p>',
            'status' => 'draft',
        ], $overrides));
    }

    // ── Authorization ────────────────────────────────────────────────────────

    public function test_guest_cannot_access_campaigns(): void
    {
        $this->get('/campaigns')->assertRedirect('/login');
    }

    public function test_user_without_permission_cannot_access_campaigns(): void
    {
        $this->actingAs($this->makeUserWithout())
            ->get('/campaigns')
            ->assertForbidden();
    }

    public function test_user_with_permission_can_access_campaigns(): void
    {
        $this->actingAs($this->makeUserWithPermission())
            ->get('/campaigns')
            ->assertOk();
    }

    // ── Index ────────────────────────────────────────────────────────────────

    public function test_index_shows_campaigns(): void
    {
        $admin = $this->makeAdmin();
        $this->createCampaign($admin);

        $this->actingAs($admin)
            ->get('/campaigns')
            ->assertInertia(fn ($page) => $page
                ->component('Campaigns/Index')
                ->has('campaigns.data', 1)
            );
    }

    // ── Create / Store ───────────────────────────────────────────────────────

    public function test_can_view_create_form(): void
    {
        $this->actingAs($this->makeAdmin())
            ->get('/campaigns/create')
            ->assertOk();
    }

    public function test_can_store_campaign(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->post('/campaigns', [
                'name' => 'My Campaign',
                'subject' => 'Newsletter',
                'body' => '<p>Content</p>',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('campaigns', [
            'name' => 'My Campaign',
            'subject' => 'Newsletter',
            'status' => 'draft',
            'user_id' => $admin->id,
        ]);
    }

    public function test_store_validates_required_fields(): void
    {
        $this->actingAs($this->makeAdmin())
            ->post('/campaigns', [])
            ->assertSessionHasErrors(['name', 'subject', 'body']);
    }

    // ── Edit / Update ────────────────────────────────────────────────────────

    public function test_can_edit_draft_campaign(): void
    {
        $admin = $this->makeAdmin();
        $campaign = $this->createCampaign($admin);

        $this->actingAs($admin)
            ->get("/campaigns/{$campaign->id}/edit")
            ->assertOk();
    }

    public function test_can_update_draft_campaign(): void
    {
        $admin = $this->makeAdmin();
        $campaign = $this->createCampaign($admin);

        $this->actingAs($admin)
            ->put("/campaigns/{$campaign->id}", [
                'name' => 'Updated',
                'subject' => 'Updated Subject',
                'body' => '<p>Updated</p>',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('campaigns', [
            'id' => $campaign->id,
            'name' => 'Updated',
        ]);
    }

    public function test_cannot_update_sent_campaign(): void
    {
        $admin = $this->makeAdmin();
        $campaign = $this->createCampaign($admin, ['status' => 'sent']);

        $this->actingAs($admin)
            ->put("/campaigns/{$campaign->id}", [
                'name' => 'Updated',
                'subject' => 'Updated',
                'body' => 'Updated',
            ])
            ->assertForbidden();
    }

    // ── Delete ───────────────────────────────────────────────────────────────

    public function test_can_delete_draft_campaign(): void
    {
        $admin = $this->makeAdmin();
        $campaign = $this->createCampaign($admin);

        $this->actingAs($admin)
            ->delete("/campaigns/{$campaign->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('campaigns', ['id' => $campaign->id]);
    }

    public function test_cannot_delete_sending_campaign(): void
    {
        $admin = $this->makeAdmin();
        $campaign = $this->createCampaign($admin, ['status' => 'sending']);

        $this->actingAs($admin)
            ->delete("/campaigns/{$campaign->id}")
            ->assertForbidden();
    }

    // ── Send ─────────────────────────────────────────────────────────────────

    public function test_can_send_draft_campaign(): void
    {
        Queue::fake();

        $admin = $this->makeAdmin();
        $campaign = $this->createCampaign($admin);
        Subscriber::create(['email' => 'sub@test.com', 'status' => 'active', 'subscribed_at' => now()]);

        $this->actingAs($admin)
            ->post("/campaigns/{$campaign->id}/send")
            ->assertRedirect();

        $campaign->refresh();
        $this->assertEquals('sending', $campaign->status);

        Queue::assertPushed(SendCampaignJob::class);
    }

    public function test_cannot_send_already_sent_campaign(): void
    {
        $admin = $this->makeAdmin();
        $campaign = $this->createCampaign($admin, ['status' => 'sent']);

        $this->actingAs($admin)
            ->post("/campaigns/{$campaign->id}/send")
            ->assertForbidden();
    }

    public function test_cannot_send_with_no_subscribers(): void
    {
        $admin = $this->makeAdmin();
        $campaign = $this->createCampaign($admin);

        $this->actingAs($admin)
            ->post("/campaigns/{$campaign->id}/send")
            ->assertStatus(422);
    }

    // ── Report ───────────────────────────────────────────────────────────────

    public function test_can_view_campaign_report(): void
    {
        $admin = $this->makeAdmin();
        $campaign = $this->createCampaign($admin, ['status' => 'sent']);

        $this->actingAs($admin)
            ->get("/campaigns/{$campaign->id}/report")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Campaigns/Report'));
    }

    // ── Schedule ─────────────────────────────────────────────────────────────

    public function test_can_schedule_draft_campaign(): void
    {
        $admin = $this->makeAdmin();
        $campaign = $this->createCampaign($admin);
        Subscriber::create(['email' => 'sub@test.com', 'status' => 'active', 'subscribed_at' => now()]);

        $scheduledAt = now()->addDay()->format('Y-m-d\TH:i');

        $this->actingAs($admin)
            ->post("/campaigns/{$campaign->id}/schedule", [
                'scheduled_at' => $scheduledAt,
            ])
            ->assertRedirect();

        $campaign->refresh();
        $this->assertEquals('scheduled', $campaign->status);
        $this->assertNotNull($campaign->scheduled_at);
    }

    public function test_cannot_schedule_sent_campaign(): void
    {
        $admin = $this->makeAdmin();
        $campaign = $this->createCampaign($admin, ['status' => 'sent']);

        $this->actingAs($admin)
            ->post("/campaigns/{$campaign->id}/schedule", [
                'scheduled_at' => now()->addDay()->format('Y-m-d\TH:i'),
            ])
            ->assertForbidden();
    }

    public function test_schedule_requires_future_date(): void
    {
        $admin = $this->makeAdmin();
        $campaign = $this->createCampaign($admin);
        Subscriber::create(['email' => 'sub@test.com', 'status' => 'active', 'subscribed_at' => now()]);

        $this->actingAs($admin)
            ->post("/campaigns/{$campaign->id}/schedule", [
                'scheduled_at' => now()->subHour()->format('Y-m-d\TH:i'),
            ])
            ->assertSessionHasErrors('scheduled_at');
    }

    public function test_can_unschedule_campaign(): void
    {
        $admin = $this->makeAdmin();
        $campaign = $this->createCampaign($admin, [
            'status' => 'scheduled',
            'scheduled_at' => now()->addDay(),
        ]);

        $this->actingAs($admin)
            ->post("/campaigns/{$campaign->id}/unschedule")
            ->assertRedirect();

        $campaign->refresh();
        $this->assertEquals('draft', $campaign->status);
        $this->assertNull($campaign->scheduled_at);
    }

    public function test_cannot_unschedule_non_scheduled_campaign(): void
    {
        $admin = $this->makeAdmin();
        $campaign = $this->createCampaign($admin, ['status' => 'draft']);

        $this->actingAs($admin)
            ->post("/campaigns/{$campaign->id}/unschedule")
            ->assertForbidden();
    }

    public function test_can_edit_scheduled_campaign(): void
    {
        $admin = $this->makeAdmin();
        $campaign = $this->createCampaign($admin, [
            'status' => 'scheduled',
            'scheduled_at' => now()->addDay(),
        ]);

        $this->actingAs($admin)
            ->get("/campaigns/{$campaign->id}/edit")
            ->assertOk();
    }

    public function test_updating_scheduled_campaign_resets_to_draft(): void
    {
        $admin = $this->makeAdmin();
        $campaign = $this->createCampaign($admin, [
            'status' => 'scheduled',
            'scheduled_at' => now()->addDay(),
        ]);

        $this->actingAs($admin)
            ->put("/campaigns/{$campaign->id}", [
                'name' => 'Updated',
                'subject' => 'Updated Subject',
                'body' => '<p>Updated</p>',
            ])
            ->assertRedirect();

        $campaign->refresh();
        $this->assertEquals('draft', $campaign->status);
        $this->assertNull($campaign->scheduled_at);
    }

    public function test_can_send_scheduled_campaign_immediately(): void
    {
        Queue::fake();

        $admin = $this->makeAdmin();
        $campaign = $this->createCampaign($admin, [
            'status' => 'scheduled',
            'scheduled_at' => now()->addDay(),
        ]);
        Subscriber::create(['email' => 'sub@test.com', 'status' => 'active', 'subscribed_at' => now()]);

        $this->actingAs($admin)
            ->post("/campaigns/{$campaign->id}/send")
            ->assertRedirect();

        $campaign->refresh();
        $this->assertEquals('sending', $campaign->status);
        $this->assertNull($campaign->scheduled_at);
    }

    // ── Bulk Delete ──────────────────────────────────────────────────────────

    public function test_bulk_delete_skips_sending_campaigns(): void
    {
        $admin = $this->makeAdmin();
        $draft = $this->createCampaign($admin, ['name' => 'Draft']);
        $sending = $this->createCampaign($admin, ['name' => 'Sending', 'status' => 'sending']);

        $this->actingAs($admin)
            ->post('/campaigns/bulk', [
                'ids' => [$draft->id, $sending->id],
                'action' => 'delete',
            ])
            ->assertRedirect();

        $this->assertDatabaseMissing('campaigns', ['id' => $draft->id]);
        $this->assertDatabaseHas('campaigns', ['id' => $sending->id]);
    }
}
