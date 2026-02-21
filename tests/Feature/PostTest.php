<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostTest extends TestCase
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

    // ── Index ─────────────────────────────────────────────────────────────────

    public function test_guest_cannot_access_posts_index(): void
    {
        $this->get('/posts')->assertRedirect('/login');
    }

    public function test_authenticated_user_can_access_posts_index(): void
    {
        $this->actingAs($this->makeUser())->get('/posts')->assertOk();
    }

    // ── Create / Store ────────────────────────────────────────────────────────

    public function test_user_can_create_a_draft_post(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->post('/posts', [
            'title'  => 'My New Post',
            'status' => 'draft',
            'body'   => '<p>Hello</p>',
        ])->assertRedirect('/posts');

        $this->assertDatabaseHas('posts', [
            'title'   => 'My New Post',
            'status'  => 'draft',
            'user_id' => $user->id,
        ]);
    }

    public function test_published_post_gets_published_at_timestamp(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->post('/posts', [
            'title'  => 'Published Post',
            'status' => 'published',
        ]);

        $post = Post::where('title', 'Published Post')->first();
        $this->assertNotNull($post->published_at);
    }

    public function test_draft_post_has_null_published_at(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->post('/posts', [
            'title'  => 'Draft Post',
            'status' => 'draft',
        ]);

        $post = Post::where('title', 'Draft Post')->first();
        $this->assertNull($post->published_at);
    }

    public function test_slug_is_auto_generated_from_title(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->post('/posts', [
            'title'  => 'Auto Slug Title',
            'status' => 'draft',
        ]);

        $this->assertDatabaseHas('posts', ['slug' => 'auto-slug-title']);
    }

    public function test_store_validates_required_title(): void
    {
        $this->actingAs($this->makeUser())
            ->post('/posts', ['status' => 'draft'])
            ->assertSessionHasErrors('title');
    }

    public function test_store_validates_status_values(): void
    {
        $this->actingAs($this->makeUser())
            ->post('/posts', ['title' => 'Test', 'status' => 'invalid'])
            ->assertSessionHasErrors('status');
    }

    // ── Edit / Update ─────────────────────────────────────────────────────────

    public function test_owner_can_edit_their_post(): void
    {
        $user = $this->makeUser();
        $post = Post::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->get("/posts/{$post->id}/edit")->assertOk();
    }

    public function test_non_owner_cannot_edit_others_post(): void
    {
        $owner = $this->makeUser();
        $other = $this->makeUser();
        $post  = Post::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($other)->get("/posts/{$post->id}/edit")->assertForbidden();
    }

    public function test_administrator_can_edit_any_post(): void
    {
        $owner = $this->makeUser();
        $admin = $this->makeAdmin();
        $post  = Post::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($admin)->get("/posts/{$post->id}/edit")->assertOk();
    }

    public function test_owner_can_update_their_post(): void
    {
        $user = $this->makeUser();
        $post = Post::factory()->create(['user_id' => $user->id, 'title' => 'Old Title']);

        $this->actingAs($user)->put("/posts/{$post->id}", [
            'title'  => 'New Title',
            'status' => 'draft',
        ])->assertRedirect('/posts');

        $this->assertDatabaseHas('posts', ['id' => $post->id, 'title' => 'New Title']);
    }

    public function test_non_owner_cannot_update_others_post(): void
    {
        $owner = $this->makeUser();
        $other = $this->makeUser();
        $post  = Post::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($other)->put("/posts/{$post->id}", [
            'title'  => 'Hacked',
            'status' => 'draft',
        ])->assertForbidden();
    }

    public function test_administrator_can_update_any_post(): void
    {
        $owner = $this->makeUser();
        $admin = $this->makeAdmin();
        $post  = Post::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($admin)->put("/posts/{$post->id}", [
            'title'  => 'Admin Updated',
            'status' => 'draft',
        ])->assertRedirect('/posts');

        $this->assertDatabaseHas('posts', ['id' => $post->id, 'title' => 'Admin Updated']);
    }

    public function test_publishing_a_draft_sets_published_at(): void
    {
        $user = $this->makeUser();
        $post = Post::factory()->draft()->create(['user_id' => $user->id]);

        $this->actingAs($user)->put("/posts/{$post->id}", [
            'title'  => $post->title,
            'status' => 'published',
        ]);

        $this->assertNotNull($post->fresh()->published_at);
    }

    public function test_reverting_to_draft_clears_published_at(): void
    {
        $user = $this->makeUser();
        $post = Post::factory()->published()->create(['user_id' => $user->id]);

        $this->actingAs($user)->put("/posts/{$post->id}", [
            'title'  => $post->title,
            'status' => 'draft',
        ]);

        $this->assertNull($post->fresh()->published_at);
    }

    // ── Destroy ───────────────────────────────────────────────────────────────

    public function test_owner_can_delete_their_post(): void
    {
        $user = $this->makeUser();
        $post = Post::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->delete("/posts/{$post->id}")->assertRedirect('/posts');
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    public function test_non_owner_cannot_delete_others_post(): void
    {
        $owner = $this->makeUser();
        $other = $this->makeUser();
        $post  = Post::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($other)->delete("/posts/{$post->id}")->assertForbidden();
        $this->assertDatabaseHas('posts', ['id' => $post->id]);
    }

    public function test_administrator_can_delete_any_post(): void
    {
        $owner = $this->makeUser();
        $admin = $this->makeAdmin();
        $post  = Post::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($admin)->delete("/posts/{$post->id}")->assertRedirect('/posts');
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    // ── Tags ──────────────────────────────────────────────────────────────────

    public function test_tags_are_synced_on_store(): void
    {
        $user = $this->makeUser();
        $tags = Tag::factory(2)->create();

        $this->actingAs($user)->post('/posts', [
            'title'   => 'Tagged Post',
            'status'  => 'draft',
            'tag_ids' => $tags->pluck('id')->toArray(),
        ]);

        $post = Post::where('title', 'Tagged Post')->first();
        $this->assertCount(2, $post->tags);
    }

    public function test_categories_are_synced_on_store(): void
    {
        $user = $this->makeUser();
        $cat1 = Category::factory()->create();
        $cat2 = Category::factory()->create();

        $this->actingAs($user)->post(route('posts.store'), [
            'title'        => 'Multi cat post',
            'status'       => 'draft',
            'category_ids' => [$cat1->id, $cat2->id],
        ]);

        $post = Post::where('title', 'Multi cat post')->first();
        $this->assertCount(2, $post->categories);
        $this->assertTrue($post->categories->contains($cat1));
        $this->assertTrue($post->categories->contains($cat2));
    }

    public function test_categories_are_synced_on_update(): void
    {
        $user = $this->makeUser();
        $post = Post::factory()->create(['user_id' => $user->id]);
        $cat1 = Category::factory()->create();
        $cat2 = Category::factory()->create();
        $post->categories()->sync([$cat1->id]);

        $this->actingAs($user)->put(route('posts.update', $post), [
            'title'        => $post->title,
            'status'       => $post->status,
            'category_ids' => [$cat2->id],
        ]);

        $post->refresh();
        $this->assertCount(1, $post->categories);
        $this->assertTrue($post->categories->contains($cat2));
        $this->assertFalse($post->categories->contains($cat1));
    }
}
