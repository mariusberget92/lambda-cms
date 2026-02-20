<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
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

    public function test_guest_cannot_access_categories(): void
    {
        $this->get('/categories')->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_categories(): void
    {
        $this->actingAs($this->makeUser())->get('/categories')->assertOk();
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function test_user_can_create_a_category(): void
    {
        $this->actingAs($this->makeUser())
            ->post('/categories', ['name' => 'Technology'])
            ->assertRedirect('/categories');

        $this->assertDatabaseHas('categories', ['name' => 'Technology', 'slug' => 'technology']);
    }

    public function test_slug_is_auto_generated(): void
    {
        $this->actingAs($this->makeUser())
            ->post('/categories', ['name' => 'My New Category']);

        $this->assertDatabaseHas('categories', ['slug' => 'my-new-category']);
    }

    public function test_store_validates_required_name(): void
    {
        $this->actingAs($this->makeUser())
            ->post('/categories', [])
            ->assertSessionHasErrors('name');
    }

    public function test_store_validates_name_max_length(): void
    {
        $this->actingAs($this->makeUser())
            ->post('/categories', ['name' => str_repeat('a', 101)])
            ->assertSessionHasErrors('name');
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function test_user_can_update_a_category(): void
    {
        $user     = $this->makeUser();
        $category = Category::factory()->create(['name' => 'Old Name', 'slug' => 'old-name']);

        $this->actingAs($user)
            ->put("/categories/{$category->id}", ['name' => 'New Name'])
            ->assertRedirect('/categories');

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'New Name']);
    }

    public function test_updating_category_regenerates_slug(): void
    {
        $user     = $this->makeUser();
        $category = Category::factory()->create(['name' => 'Old Name', 'slug' => 'old-name']);

        $this->actingAs($user)
            ->put("/categories/{$category->id}", ['name' => 'New Name']);

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'slug' => 'new-name']);
    }

    // ── Destroy ───────────────────────────────────────────────────────────────

    public function test_user_can_delete_a_category(): void
    {
        $user     = $this->makeUser();
        $category = Category::factory()->create();

        $this->actingAs($user)
            ->delete("/categories/{$category->id}")
            ->assertRedirect('/categories');

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }
}
