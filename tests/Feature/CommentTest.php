<?php

namespace Tests\Feature;

use App\Jobs\SendNewCommentNotification;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Setting;
use App\Models\User;
use App\Services\SettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CommentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->markAsInstalled();
        $this->seedRolesAndPermissions();
    }

    private function makeUser(): User
    {
        return User::factory()->create()->assignRole('user');
    }

    private function makeAdmin(): User
    {
        return User::factory()->create()->assignRole('administrator');
    }

    // ── Model structure ───────────────────────────────────────────────────────

    public function test_comment_belongs_to_post(): void
    {
        $post = Post::factory()->published()->create();
        $comment = Comment::factory()->create(['post_id' => $post->id]);

        $this->assertTrue($comment->post->is($post));
    }

    public function test_post_has_many_comments(): void
    {
        $post = Post::factory()->published()->create();
        Comment::factory(3)->create(['post_id' => $post->id]);

        $this->assertCount(3, $post->comments);
    }

    // ── Public store ──────────────────────────────────────────────────────────

    public function test_guest_can_submit_comment(): void
    {
        $post = Post::factory()->published()->create();

        $response = $this->post(route('comments.store', $post->slug), [
            'author_name' => 'Alice',
            'author_email' => 'alice@example.com',
            'body' => 'Great post!',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('comments', [
            'post_id' => $post->id,
            'author_name' => 'Alice',
            'status' => 'pending',
        ]);
    }

    public function test_honeypot_silently_discards_comment(): void
    {
        $post = Post::factory()->published()->create();

        $this->post(route('comments.store', $post->slug), [
            'author_name' => 'Bot',
            'body' => 'Spam',
            'website' => 'http://spam.example.com',
        ]);

        $this->assertDatabaseMissing('comments', ['author_name' => 'Bot']);
    }

    public function test_comment_requires_name_and_body(): void
    {
        $post = Post::factory()->published()->create();

        $this->post(route('comments.store', $post->slug), [])
            ->assertSessionHasErrors(['author_name', 'body']);
    }

    public function test_authenticated_user_comment_stores_user_id(): void
    {
        $user = $this->makeUser();
        $post = Post::factory()->published()->create();

        $this->actingAs($user)->post(route('comments.store', $post->slug), [
            'author_name' => $user->name,
            'body' => 'Logged in comment',
        ]);

        $this->assertDatabaseHas('comments', [
            'post_id' => $post->id,
            'user_id' => $user->id,
        ]);
    }

    // ── Admin actions ─────────────────────────────────────────────────────────

    public function test_non_admin_cannot_access_comments_index(): void
    {
        // Spatie role middleware redirects non-admins to dashboard instead of 403
        $user = $this->makeUser();
        $this->actingAs($user)->get(route('comments.index'))->assertRedirect(route('dashboard'));
    }

    public function test_admin_can_view_comments_index(): void
    {
        $admin = $this->makeAdmin();
        $post = Post::factory()->published()->create();
        Comment::factory()->create(['post_id' => $post->id, 'status' => 'pending']);

        $this->actingAs($admin)->get(route('comments.index'))->assertOk();
    }

    public function test_admin_can_approve_comment(): void
    {
        $admin = $this->makeAdmin();
        $post = Post::factory()->published()->create();
        $comment = Comment::factory()->create(['post_id' => $post->id, 'status' => 'pending']);

        $this->actingAs($admin)->patch(route('comments.approve', $comment))->assertRedirect();
        $this->assertDatabaseHas('comments', ['id' => $comment->id, 'status' => 'approved']);
    }

    public function test_admin_can_reject_comment(): void
    {
        $admin = $this->makeAdmin();
        $post = Post::factory()->published()->create();
        $comment = Comment::factory()->create(['post_id' => $post->id, 'status' => 'pending']);

        $this->actingAs($admin)->patch(route('comments.reject', $comment))->assertRedirect();
        $this->assertDatabaseHas('comments', ['id' => $comment->id, 'status' => 'rejected']);
    }

    public function test_admin_can_delete_comment(): void
    {
        $admin = $this->makeAdmin();
        $post = Post::factory()->published()->create();
        $comment = Comment::factory()->create(['post_id' => $post->id]);

        $this->actingAs($admin)->delete(route('comments.destroy', $comment))->assertRedirect();
        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }

    public function test_admin_can_bulk_approve(): void
    {
        $admin = $this->makeAdmin();
        $post = Post::factory()->published()->create();
        $ids = Comment::factory(3)->create(['post_id' => $post->id, 'status' => 'pending'])->pluck('id')->toArray();

        $this->actingAs($admin)->post(route('comments.bulk'), ['action' => 'approve', 'ids' => $ids])->assertRedirect();
        $this->assertEquals(3, Comment::whereIn('id', $ids)->where('status', 'approved')->count());
    }

    public function test_notification_dispatched_on_comment_store(): void
    {
        Queue::fake();

        $post = Post::factory()->published()->create();

        $this->post(route('comments.store', $post->slug), [
            'author_name' => 'Alice',
            'body' => 'Test notification',
        ]);

        Queue::assertPushed(SendNewCommentNotification::class);
    }

    // -- Comments settings --------------------------------------------------------

    private function seedCommentSettings(bool $enabled = true, int $perPage = 10): void
    {
        Setting::create(['group' => 'comments', 'key' => 'comments.enabled',  'value' => $enabled ? '1' : '0', 'type' => 'boolean']);
        Setting::create(['group' => 'comments', 'key' => 'comments.per_page', 'value' => (string) $perPage,     'type' => 'integer']);
        app(SettingService::class)->bust();
    }

    public function test_comments_store_rejected_when_comments_disabled(): void
    {
        $this->seedCommentSettings(enabled: false);
        $post = Post::factory()->published()->create();

        $this->post(route('comments.store', $post->slug), [
            'author_name' => 'Alice',
            'body' => 'Hello!',
        ])->assertForbidden();
    }

    public function test_settings_comments_group_saves_correctly(): void
    {
        $this->seedCommentSettings();
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->put(route('settings.update', 'comments'), [
            'comments.enabled' => '1',
            'comments.per_page' => 20,
        ])->assertRedirect();

        $this->assertDatabaseHas('settings', ['key' => 'comments.enabled',  'value' => '1']);
        $this->assertDatabaseHas('settings', ['key' => 'comments.per_page', 'value' => '20']);
    }

    public function test_settings_comments_validates_per_page_range(): void
    {
        $this->seedCommentSettings();
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->put(route('settings.update', 'comments'), [
            'comments.enabled' => '1',
            'comments.per_page' => 999,
        ])->assertSessionHasErrors('comments.per_page');

        $this->actingAs($admin)->put(route('settings.update', 'comments'), [
            'comments.enabled' => '1',
            'comments.per_page' => 2,
        ])->assertSessionHasErrors('comments.per_page');
    }

    // ── JSON comments endpoint ────────────────────────────────────────────────

    public function test_comments_json_endpoint_returns_paginated_comments(): void
    {
        $this->seedCommentSettings(perPage: 3);
        $post = Post::factory()->published()->create();
        Comment::factory(5)->approved()->create(['post_id' => $post->id]);

        $response = $this->getJson("/blog/{$post->slug}/comments?page=1");

        $response->assertOk()
            ->assertJsonStructure(['data', 'has_more', 'total'])
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('total', 5)
            ->assertJsonPath('has_more', true);
    }

    public function test_comments_json_endpoint_respects_page_param(): void
    {
        $this->seedCommentSettings(perPage: 3);
        $post = Post::factory()->published()->create();
        Comment::factory(5)->approved()->create(['post_id' => $post->id]);

        $response = $this->getJson("/blog/{$post->slug}/comments?page=2");

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('has_more', false);
    }

    public function test_comments_json_endpoint_only_returns_approved(): void
    {
        $this->seedCommentSettings(perPage: 10);
        $post = Post::factory()->published()->create();
        Comment::factory(3)->approved()->create(['post_id' => $post->id]);
        Comment::factory(2)->pending()->create(['post_id' => $post->id]);

        $response = $this->getJson("/blog/{$post->slug}/comments?page=1");

        $response->assertOk()->assertJsonCount(3, 'data');
    }

    public function test_comments_json_endpoint_returns_404_for_unknown_slug(): void
    {
        $this->seedCommentSettings();

        $this->getJson('/blog/does-not-exist/comments')->assertNotFound();
    }

    public function test_comments_json_endpoint_returns_404_for_draft_post(): void
    {
        $this->seedCommentSettings();
        $post = Post::factory()->create(['status' => 'draft']);

        $this->getJson("/blog/{$post->slug}/comments")->assertNotFound();
    }

    public function test_comments_json_endpoint_returns_empty_when_no_comments(): void
    {
        $this->seedCommentSettings(perPage: 10);
        $post = Post::factory()->published()->create();

        $response = $this->getJson("/blog/{$post->slug}/comments");

        $response->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('has_more', false)
            ->assertJsonPath('total', 0);
    }

    public function test_comments_json_endpoint_excludes_rejected_comments(): void
    {
        $this->seedCommentSettings(perPage: 10);
        $post = Post::factory()->published()->create();
        Comment::factory(2)->approved()->create(['post_id' => $post->id]);
        Comment::factory(3)->create(['post_id' => $post->id, 'status' => 'rejected']);

        $response = $this->getJson("/blog/{$post->slug}/comments");

        $response->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_comments_store_rejected_when_post_comments_disabled(): void
    {
        // Global setting ON — post flag OFF → should still 403
        $this->seedCommentSettings(enabled: true);
        $post = Post::factory()->published()->create(['comments_enabled' => false]);

        $this->post(route('comments.store', $post->slug), [
            'author_name' => 'Alice',
            'body' => 'Hello!',
        ])->assertForbidden();
    }

    public function test_comments_json_endpoint_returns_403_when_post_comments_disabled(): void
    {
        $this->seedCommentSettings(enabled: true);
        $post = Post::factory()->published()->create(['comments_enabled' => false]);

        $this->getJson("/blog/{$post->slug}/comments")->assertForbidden();
    }
}
