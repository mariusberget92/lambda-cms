<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $post    = Post::factory()->published()->create();
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
            'author_name'  => 'Alice',
            'author_email' => 'alice@example.com',
            'body'         => 'Great post!',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('comments', [
            'post_id'     => $post->id,
            'author_name' => 'Alice',
            'status'      => 'pending',
        ]);
    }

    public function test_honeypot_silently_discards_comment(): void
    {
        $post = Post::factory()->published()->create();

        $this->post(route('comments.store', $post->slug), [
            'author_name' => 'Bot',
            'body'        => 'Spam',
            'website'     => 'http://spam.example.com',
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
            'body'        => 'Logged in comment',
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
        $post  = Post::factory()->published()->create();
        Comment::factory()->create(['post_id' => $post->id, 'status' => 'pending']);

        $this->actingAs($admin)->get(route('comments.index'))->assertOk();
    }

    public function test_admin_can_approve_comment(): void
    {
        $admin   = $this->makeAdmin();
        $post    = Post::factory()->published()->create();
        $comment = Comment::factory()->create(['post_id' => $post->id, 'status' => 'pending']);

        $this->actingAs($admin)->patch(route('comments.approve', $comment))->assertRedirect();
        $this->assertDatabaseHas('comments', ['id' => $comment->id, 'status' => 'approved']);
    }

    public function test_admin_can_reject_comment(): void
    {
        $admin   = $this->makeAdmin();
        $post    = Post::factory()->published()->create();
        $comment = Comment::factory()->create(['post_id' => $post->id, 'status' => 'pending']);

        $this->actingAs($admin)->patch(route('comments.reject', $comment))->assertRedirect();
        $this->assertDatabaseHas('comments', ['id' => $comment->id, 'status' => 'rejected']);
    }

    public function test_admin_can_delete_comment(): void
    {
        $admin   = $this->makeAdmin();
        $post    = Post::factory()->published()->create();
        $comment = Comment::factory()->create(['post_id' => $post->id]);

        $this->actingAs($admin)->delete(route('comments.destroy', $comment))->assertRedirect();
        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }

    public function test_admin_can_bulk_approve(): void
    {
        $admin = $this->makeAdmin();
        $post  = Post::factory()->published()->create();
        $ids   = Comment::factory(3)->create(['post_id' => $post->id, 'status' => 'pending'])->pluck('id')->toArray();

        $this->actingAs($admin)->post(route('comments.bulk'), ['action' => 'approve', 'ids' => $ids])->assertRedirect();
        $this->assertEquals(3, Comment::whereIn('id', $ids)->where('status', 'approved')->count());
    }

    public function test_notification_dispatched_on_comment_store(): void
    {
        \Illuminate\Support\Facades\Queue::fake();

        $post = Post::factory()->published()->create();

        $this->post(route('comments.store', $post->slug), [
            'author_name' => 'Alice',
            'body'        => 'Test notification',
        ]);

        \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\SendNewCommentNotification::class);
    }
}
