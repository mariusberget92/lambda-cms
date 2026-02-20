<?php

namespace Tests\Unit;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategorySlugTest extends TestCase
{
    use RefreshDatabase;

    public function test_generates_slug_from_name(): void
    {
        $slug = Category::generateSlug('My Category');
        $this->assertSame('my-category', $slug);
    }

    public function test_appends_counter_on_collision(): void
    {
        Category::factory()->create(['name' => 'Tech', 'slug' => 'tech']);

        $slug = Category::generateSlug('Tech');
        $this->assertSame('tech-1', $slug);
    }

    public function test_excludes_own_id_when_updating(): void
    {
        $category = Category::factory()->create(['name' => 'Design', 'slug' => 'design']);

        $slug = Category::generateSlug('Design', $category->id);
        $this->assertSame('design', $slug);
    }
}
