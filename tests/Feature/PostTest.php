<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\Setting;
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

    // ── Scheduled Scope ───────────────────────────────────────────────────────

    public function test_scope_scheduled_returns_only_scheduled_posts(): void
    {
        $user = $this->makeUser();
        Post::factory()->create(['user_id' => $user->id, 'status' => 'published']);
        Post::factory()->create(['user_id' => $user->id, 'status' => 'draft']);
        $scheduled = Post::factory()->create([
            'user_id'      => $user->id,
            'status'       => 'scheduled',
            'published_at' => now()->addDay(),
        ]);

        $results = Post::scheduled()->get();

        $this->assertCount(1, $results);
        $this->assertEquals($scheduled->id, $results->first()->id);
    }

    public function test_is_scheduled_returns_true_for_scheduled_post(): void
    {
        $user = $this->makeUser();
        $post = Post::factory()->create([
            'user_id'      => $user->id,
            'status'       => 'scheduled',
            'published_at' => now()->addDay(),
        ]);

        $this->assertTrue($post->isScheduled());
    }

    public function test_is_scheduled_returns_false_for_draft_post(): void
    {
        $user = $this->makeUser();
        $post = Post::factory()->create(['user_id' => $user->id, 'status' => 'draft']);

        $this->assertFalse($post->isScheduled());
    }

    public function test_scope_published_excludes_scheduled_posts(): void
    {
        $user = $this->makeUser();
        Post::factory()->create(['user_id' => $user->id, 'status' => 'published']);
        Post::factory()->create([
            'user_id'      => $user->id,
            'status'       => 'scheduled',
            'published_at' => now()->addDay(),
        ]);

        $this->assertCount(1, Post::published()->get());
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

    // ── Comments enabled ──────────────────────────────────────────────────────────

    public function test_post_comments_enabled_defaults_to_true(): void
    {
        $post = Post::factory()->published()->create();

        $this->assertTrue($post->comments_enabled);
    }

    public function test_post_comments_open_returns_false_when_flag_is_false(): void
    {
        $post = Post::factory()->published()->create(['comments_enabled' => false]);

        $this->assertFalse($post->commentsOpen());
    }

    public function test_post_comments_open_returns_true_when_flag_is_true(): void
    {
        $post = Post::factory()->published()->create(['comments_enabled' => true]);

        $this->assertTrue($post->commentsOpen());
    }

    public function test_post_comments_open_returns_false_when_global_setting_disabled(): void
    {
        Setting::create(['group' => 'comments', 'key' => 'comments.enabled', 'value' => '0', 'type' => 'boolean']);
        app(\App\Services\SettingService::class)->bust();

        $post = Post::factory()->published()->create(['comments_enabled' => true]);

        $this->assertFalse($post->commentsOpen());
    }

    // ── PostController comments_enabled ───────────────────────────────────────

    public function test_post_store_with_comments_disabled_persists(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->post('/posts', [
            'title'            => 'No Comments Post',
            'status'           => 'draft',
            'body'             => '<p>Hello</p>',
            'comments_enabled' => false,
        ])->assertRedirect('/posts');

        $this->assertDatabaseHas('posts', [
            'title'            => 'No Comments Post',
            'comments_enabled' => false,
        ]);
    }

    public function test_post_store_defaults_comments_enabled_to_true(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->post('/posts', [
            'title'  => 'Default Comments Post',
            'status' => 'draft',
            'body'   => '<p>Hello</p>',
            // no comments_enabled field
        ])->assertRedirect('/posts');

        $this->assertDatabaseHas('posts', [
            'title'            => 'Default Comments Post',
            'comments_enabled' => true,
        ]);
    }

    public function test_post_update_can_disable_comments(): void
    {
        $user = $this->makeUser();
        $post = Post::factory()->create(['user_id' => $user->id, 'comments_enabled' => true]);

        $this->actingAs($user)->put("/posts/{$post->id}", [
            'title'            => $post->title,
            'status'           => $post->status,
            'body'             => $post->body ?? '',
            'comments_enabled' => false,
        ])->assertRedirect('/posts');

        $this->assertDatabaseHas('posts', [
            'id'               => $post->id,
            'comments_enabled' => false,
        ]);
    }

    public function test_post_update_preserves_comments_disabled_when_field_absent(): void
    {
        $user = $this->makeUser();
        $post = Post::factory()->create(['user_id' => $user->id, 'comments_enabled' => false]);

        $this->actingAs($user)->put("/posts/{$post->id}", [
            'title'  => $post->title,
            'status' => $post->status,
            'body'   => $post->body ?? '',
            // comments_enabled deliberately omitted
        ])->assertRedirect('/posts');

        $this->assertDatabaseHas('posts', [
            'id'               => $post->id,
            'comments_enabled' => false,
        ]);
    }

    public function test_post_has_nullable_meta_title(): void
    {
        $post = Post::factory()->create(['meta_title' => null]);
        $this->assertNull($post->fresh()->meta_title);
    }

    public function test_post_has_nullable_meta_description(): void
    {
        $post = Post::factory()->create(['meta_description' => null]);
        $this->assertNull($post->fresh()->meta_description);
    }

    public function test_post_meta_title_is_fillable(): void
    {
        $post = Post::factory()->create(['meta_title' => 'Custom SEO Title']);
        $this->assertSame('Custom SEO Title', $post->fresh()->meta_title);
    }

    public function test_post_meta_description_is_fillable(): void
    {
        $post = Post::factory()->create(['meta_description' => 'Custom meta desc']);
        $this->assertSame('Custom meta desc', $post->fresh()->meta_description);
    }

    // ── SEO meta fields (PostController store/update/edit) ────────────────────

    public function test_post_store_persists_meta_title_and_meta_description(): void
    {
        $admin = $this->makeAdmin();
        $category = Category::factory()->create();

        $this->actingAs($admin)->post(route('posts.store'), [
            'title'            => 'SEO Test Post',
            'body'             => '<p>body</p>',
            'status'           => 'draft',
            'category_ids'     => [$category->id],
            'tag_ids'          => [],
            'meta_title'       => 'Custom SEO Title',
            'meta_description' => 'Custom meta description',
        ])->assertRedirect();

        $this->assertDatabaseHas('posts', [
            'title'            => 'SEO Test Post',
            'meta_title'       => 'Custom SEO Title',
            'meta_description' => 'Custom meta description',
        ]);
    }

    public function test_post_store_defaults_meta_fields_to_null_when_absent(): void
    {
        $admin = $this->makeAdmin();
        $category = Category::factory()->create();

        $this->actingAs($admin)->post(route('posts.store'), [
            'title'        => 'No SEO Post',
            'body'         => '<p>body</p>',
            'status'       => 'draft',
            'category_ids' => [$category->id],
            'tag_ids'      => [],
        ])->assertRedirect();

        $post = Post::where('title', 'No SEO Post')->first();
        $this->assertNull($post->meta_title);
        $this->assertNull($post->meta_description);
    }

    public function test_post_update_persists_meta_fields(): void
    {
        $admin = $this->makeAdmin();
        $post = Post::factory()->create(['user_id' => $admin->id, 'meta_title' => null]);

        $this->actingAs($admin)->put(route('posts.update', $post), [
            'title'            => $post->title,
            'body'             => $post->body,
            'status'           => $post->status,
            'category_ids'     => [],
            'tag_ids'          => [],
            'meta_title'       => 'Updated SEO Title',
            'meta_description' => 'Updated meta desc',
        ])->assertRedirect();

        $this->assertSame('Updated SEO Title', $post->fresh()->meta_title);
        $this->assertSame('Updated meta desc', $post->fresh()->meta_description);
    }

    public function test_post_update_preserves_meta_fields_when_absent_from_request(): void
    {
        $admin = $this->makeAdmin();
        $post = Post::factory()->create([
            'user_id'          => $admin->id,
            'meta_title'       => 'Existing SEO Title',
            'meta_description' => 'Existing meta desc',
        ]);

        $this->actingAs($admin)->put(route('posts.update', $post), [
            'title'        => $post->title,
            'body'         => $post->body,
            'status'       => $post->status,
            'category_ids' => [],
            'tag_ids'      => [],
        ])->assertRedirect();

        $this->assertSame('Existing SEO Title', $post->fresh()->meta_title);
        $this->assertSame('Existing meta desc', $post->fresh()->meta_description);
    }

    public function test_post_edit_includes_meta_fields_in_props(): void
    {
        $admin = $this->makeAdmin();
        $post = Post::factory()->create([
            'user_id'          => $admin->id,
            'meta_title'       => 'My SEO Title',
            'meta_description' => 'My meta desc',
        ]);

        $this->actingAs($admin)->get(route('posts.edit', $post))
            ->assertInertia(fn ($page) => $page
                ->where('post.meta_title', 'My SEO Title')
                ->where('post.meta_description', 'My meta desc')
            );
    }

    public function test_post_can_store_meta_keywords(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->post('/posts', [
            'title'         => 'Keywords Post',
            'status'        => 'draft',
            'meta_keywords' => 'laravel, cms, blog',
        ])->assertRedirect('/posts');

        $this->assertDatabaseHas('posts', [
            'title'         => 'Keywords Post',
            'meta_keywords' => 'laravel, cms, blog',
        ]);
    }

    public function test_post_can_update_meta_keywords(): void
    {
        $user = $this->makeUser();
        $post = Post::factory()->create(['user_id' => $user->id, 'meta_keywords' => 'old, keywords']);

        $this->actingAs($user)->put("/posts/{$post->id}", [
            'title'         => $post->title,
            'status'        => $post->status,
            'meta_keywords' => 'new, keywords',
        ])->assertRedirect('/posts');

        $this->assertDatabaseHas('posts', ['id' => $post->id, 'meta_keywords' => 'new, keywords']);
    }

    public function test_edit_page_includes_meta_keywords(): void
    {
        $user = $this->makeUser();
        $post = Post::factory()->create(['user_id' => $user->id, 'meta_keywords' => 'test, keywords']);

        $this->withoutVite();
        $this->actingAs($user)->get("/posts/{$post->id}/edit")
            ->assertInertia(
                fn ($page) => $page
                    ->component('Posts/Edit')
                    ->where('post.meta_keywords', 'test, keywords')
            );
    }

    // ── Scheduling ────────────────────────────────────────────────────────────

    public function test_can_create_scheduled_post_with_future_date(): void
    {
        $user     = $this->makeUser();
        $future   = now()->addDay()->format('Y-m-d\TH:i');

        $this->actingAs($user)->post('/posts', [
            'title'        => 'Scheduled Post',
            'status'       => 'scheduled',
            'published_at' => $future,
        ])->assertRedirect('/posts');

        $post = Post::where('title', 'Scheduled Post')->first();
        $this->assertEquals('scheduled', $post->status);
        $this->assertEquals(
            \Illuminate\Support\Carbon::parse($future)->toDateTimeString(),
            $post->published_at->toDateTimeString()
        );
    }

    public function test_cannot_schedule_post_without_published_at(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->post('/posts', [
            'title'  => 'Bad Schedule',
            'status' => 'scheduled',
        ])->assertSessionHasErrors('published_at');
    }

    public function test_cannot_schedule_post_with_past_published_at(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->post('/posts', [
            'title'        => 'Past Schedule',
            'status'       => 'scheduled',
            'published_at' => now()->subHour()->format('Y-m-d\TH:i'),
        ])->assertSessionHasErrors('published_at');
    }

    public function test_saving_as_draft_clears_published_at(): void
    {
        $user = $this->makeUser();
        $post = Post::factory()->create([
            'user_id'      => $user->id,
            'status'       => 'scheduled',
            'published_at' => now()->addDay(),
        ]);

        $this->actingAs($user)->put("/posts/{$post->id}", [
            'title'  => $post->title,
            'status' => 'draft',
        ])->assertRedirect('/posts');

        $this->assertNull($post->fresh()->published_at);
        $this->assertEquals('draft', $post->fresh()->status);
    }

    public function test_can_reschedule_a_published_post(): void
    {
        $user = $this->makeUser();
        $post = Post::factory()->create([
            'user_id'      => $user->id,
            'status'       => 'published',
            'published_at' => now()->subDay(),
        ]);

        $future = now()->addDay()->format('Y-m-d\TH:i');

        $this->actingAs($user)->put("/posts/{$post->id}", [
            'title'        => $post->title,
            'status'       => 'scheduled',
            'published_at' => $future,
        ])->assertRedirect('/posts');

        $this->assertEquals('scheduled', $post->fresh()->status);
        $this->assertEquals(
            \Illuminate\Support\Carbon::parse($future)->toDateTimeString(),
            $post->fresh()->published_at->toDateTimeString()
        );
    }

    public function test_republishing_preserves_original_published_at(): void
    {
        $user      = $this->makeUser();
        $original  = now()->subDays(5);
        $post      = Post::factory()->create([
            'user_id'      => $user->id,
            'status'       => 'published',
            'published_at' => $original,
        ]);

        $this->actingAs($user)->put("/posts/{$post->id}", [
            'title'        => $post->title,
            'status'       => 'published',
            'published_at' => now()->format('Y-m-d\TH:i'), // incoming value must be ignored
        ])->assertRedirect('/posts');

        $this->assertEquals(
            $original->toDateTimeString(),
            $post->fresh()->published_at->toDateTimeString()
        );
    }

    // ── Publish Scheduled Command ─────────────────────────────────────────────

    public function test_command_publishes_overdue_scheduled_posts(): void
    {
        $user = $this->makeUser();
        $post = Post::factory()->create([
            'user_id'      => $user->id,
            'status'       => 'scheduled',
            'published_at' => now()->subMinute(),
        ]);

        $this->artisan('posts:publish-scheduled')->assertSuccessful();

        $this->assertEquals('published', $post->fresh()->status);
    }

    public function test_command_does_not_publish_future_scheduled_posts(): void
    {
        $user = $this->makeUser();
        $post = Post::factory()->create([
            'user_id'      => $user->id,
            'status'       => 'scheduled',
            'published_at' => now()->addHour(),
        ]);

        $this->artisan('posts:publish-scheduled')->assertSuccessful();

        $this->assertEquals('scheduled', $post->fresh()->status);
    }

    public function test_command_does_not_affect_draft_or_published_posts(): void
    {
        $user    = $this->makeUser();
        $draft   = Post::factory()->create(['user_id' => $user->id, 'status' => 'draft']);
        $published = Post::factory()->create([
            'user_id'      => $user->id,
            'status'       => 'published',
            'published_at' => now()->subDay(),
        ]);

        $this->artisan('posts:publish-scheduled')->assertSuccessful();

        $this->assertEquals('draft',     $draft->fresh()->status);
        $this->assertEquals('published', $published->fresh()->status);
    }

    public function test_command_preserves_original_published_at_after_publishing(): void
    {
        $user   = $this->makeUser();
        $target = now()->subMinutes(5);
        $post   = Post::factory()->create([
            'user_id'      => $user->id,
            'status'       => 'scheduled',
            'published_at' => $target,
        ]);

        $this->artisan('posts:publish-scheduled')->assertSuccessful();

        $this->assertEquals(
            $target->toDateTimeString(),
            $post->fresh()->published_at->toDateTimeString()
        );
    }
}
