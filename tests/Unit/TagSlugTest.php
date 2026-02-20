<?php

namespace Tests\Unit;

use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagSlugTest extends TestCase
{
    use RefreshDatabase;

    public function test_generates_slug_from_name(): void
    {
        $slug = Tag::generateSlug('Laravel');
        $this->assertSame('laravel', $slug);
    }

    public function test_appends_counter_on_collision(): void
    {
        Tag::factory()->create(['name' => 'PHP', 'slug' => 'php']);

        $slug = Tag::generateSlug('PHP');
        $this->assertSame('php-1', $slug);
    }

    public function test_excludes_own_id_when_updating(): void
    {
        $tag = Tag::factory()->create(['name' => 'Vue', 'slug' => 'vue']);

        $slug = Tag::generateSlug('Vue', $tag->id);
        $this->assertSame('vue', $slug);
    }
}
