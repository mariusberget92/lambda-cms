<?php

namespace Tests\Feature;

use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SubscriberTest extends TestCase
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

    // ── Authorization ────────────────────────────────────────────────────────

    public function test_guest_cannot_access_subscribers(): void
    {
        $this->get('/subscribers')->assertRedirect('/login');
    }

    public function test_user_without_permission_cannot_access_subscribers(): void
    {
        $this->actingAs($this->makeUserWithout())
            ->get('/subscribers')
            ->assertForbidden();
    }

    public function test_user_with_permission_can_access_subscribers(): void
    {
        $this->actingAs($this->makeUserWithPermission())
            ->get('/subscribers')
            ->assertOk();
    }

    public function test_admin_can_access_subscribers(): void
    {
        $this->actingAs($this->makeAdmin())
            ->get('/subscribers')
            ->assertOk();
    }

    // ── Index ────────────────────────────────────────────────────────────────

    public function test_index_shows_subscriber_counts(): void
    {
        Subscriber::create(['email' => 'a@test.com', 'status' => 'active', 'subscribed_at' => now()]);
        Subscriber::create(['email' => 'b@test.com', 'status' => 'unsubscribed', 'subscribed_at' => now()]);

        $this->actingAs($this->makeAdmin())
            ->get('/subscribers')
            ->assertInertia(fn ($page) => $page
                ->where('counts.all', 2)
                ->where('counts.active', 1)
                ->where('counts.unsubscribed', 1)
            );
    }

    public function test_index_filters_by_status(): void
    {
        Subscriber::create(['email' => 'a@test.com', 'status' => 'active', 'subscribed_at' => now()]);
        Subscriber::create(['email' => 'b@test.com', 'status' => 'unsubscribed', 'subscribed_at' => now()]);

        $this->actingAs($this->makeAdmin())
            ->get('/subscribers?status=active')
            ->assertInertia(fn ($page) => $page
                ->has('subscribers.data', 1)
            );
    }

    // ── Delete ───────────────────────────────────────────────────────────────

    public function test_can_delete_subscriber(): void
    {
        $sub = Subscriber::create(['email' => 'del@test.com', 'subscribed_at' => now()]);

        $this->actingAs($this->makeAdmin())
            ->delete("/subscribers/{$sub->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('subscribers', ['id' => $sub->id]);
    }

    // ── Bulk Delete ──────────────────────────────────────────────────────────

    public function test_can_bulk_delete_subscribers(): void
    {
        $s1 = Subscriber::create(['email' => 'b1@test.com', 'subscribed_at' => now()]);
        $s2 = Subscriber::create(['email' => 'b2@test.com', 'subscribed_at' => now()]);

        $this->actingAs($this->makeAdmin())
            ->post('/subscribers/bulk', [
                'ids' => [$s1->id, $s2->id],
                'action' => 'delete',
            ])
            ->assertRedirect();

        $this->assertEquals(0, Subscriber::count());
    }

    // ── Export ────────────────────────────────────────────────────────────────

    public function test_can_export_subscribers_csv(): void
    {
        Subscriber::create(['email' => 'export@test.com', 'name' => 'Test', 'subscribed_at' => now()]);

        $response = $this->actingAs($this->makeAdmin())
            ->get('/subscribers/export');

        $response->assertOk();
        $this->assertStringStartsWith('text/csv', $response->headers->get('Content-Type'));
    }

    // ── Import ───────────────────────────────────────────────────────────────

    public function test_can_view_import_form(): void
    {
        $this->actingAs($this->makeAdmin())
            ->get('/subscribers/import')
            ->assertOk();
    }

    public function test_can_preview_csv_import(): void
    {
        Storage::fake('local');

        $csv = "email,name\ntest@example.com,Test User\n";
        $file = UploadedFile::fake()->createWithContent('subscribers.csv', $csv);

        $this->actingAs($this->makeAdmin())
            ->post('/subscribers/import/preview', ['file' => $file])
            ->assertRedirect(route('subscribers.import'));
    }

    // ── Model ────────────────────────────────────────────────────────────────

    public function test_token_auto_generated_on_create(): void
    {
        $sub = Subscriber::create(['email' => 'auto@test.com', 'subscribed_at' => now()]);
        $this->assertNotNull($sub->token);
        $this->assertEquals(64, strlen($sub->token));
    }

    public function test_active_scope(): void
    {
        Subscriber::create(['email' => 'act@test.com', 'status' => 'active', 'subscribed_at' => now()]);
        Subscriber::create(['email' => 'unsub@test.com', 'status' => 'unsubscribed', 'subscribed_at' => now()]);

        $this->assertEquals(1, Subscriber::active()->count());
    }

    public function test_search_scope(): void
    {
        Subscriber::create(['email' => 'find@test.com', 'name' => 'Findable', 'subscribed_at' => now()]);
        Subscriber::create(['email' => 'other@test.com', 'name' => 'Other', 'subscribed_at' => now()]);

        $this->assertEquals(1, Subscriber::search('findable')->count());
        $this->assertEquals(1, Subscriber::search('find@')->count());
    }
}
