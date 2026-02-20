<?php

namespace Tests\Feature;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagTest extends TestCase
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

    // ── Index ─────────────────────────────────────────────────────────────────

    public function test_guest_cannot_access_tags(): void
    {
        $this->get('/tags')->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_tags(): void
    {
        $this->actingAs($this->makeUser())->get('/tags')->assertOk();
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function test_user_can_create_a_tag(): void
    {
        $this->actingAs($this->makeUser())
            ->post('/tags', ['name' => 'Laravel'])
            ->assertRedirect('/tags');

        $this->assertDatabaseHas('tags', ['name' => 'Laravel', 'slug' => 'laravel']);
    }

    public function test_slug_is_auto_generated(): void
    {
        $this->actingAs($this->makeUser())
            ->post('/tags', ['name' => 'Vue JS']);

        $this->assertDatabaseHas('tags', ['slug' => 'vue-js']);
    }

    public function test_store_validates_required_name(): void
    {
        $this->actingAs($this->makeUser())
            ->post('/tags', [])
            ->assertSessionHasErrors('name');
    }

    public function test_store_validates_name_max_length(): void
    {
        $this->actingAs($this->makeUser())
            ->post('/tags', ['name' => str_repeat('a', 61)])
            ->assertSessionHasErrors('name');
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function test_user_can_update_a_tag(): void
    {
        $user = $this->makeUser();
        $tag  = Tag::factory()->create(['name' => 'Old Tag', 'slug' => 'old-tag']);

        $this->actingAs($user)
            ->put("/tags/{$tag->id}", ['name' => 'New Tag'])
            ->assertRedirect('/tags');

        $this->assertDatabaseHas('tags', ['id' => $tag->id, 'name' => 'New Tag', 'slug' => 'new-tag']);
    }

    // ── Destroy ───────────────────────────────────────────────────────────────

    public function test_user_can_delete_a_tag(): void
    {
        $user = $this->makeUser();
        $tag  = Tag::factory()->create();

        $this->actingAs($user)
            ->delete("/tags/{$tag->id}")
            ->assertRedirect('/tags');

        $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
    }
}
