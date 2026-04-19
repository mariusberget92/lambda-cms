<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Services\QueryBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QueryBuilderTest extends TestCase
{
    use RefreshDatabase;

    private QueryBuilder $qb;

    protected function setUp(): void
    {
        parent::setUp();
        $this->qb = new QueryBuilder();
    }

    public function test_filter_logic_or_returns_union_of_matches(): void
    {
        Category::factory()->create(['name' => 'Alpha', 'slug' => 'alpha']);
        Category::factory()->create(['name' => 'Beta',  'slug' => 'beta']);
        Category::factory()->create(['name' => 'Gamma', 'slug' => 'gamma']);

        $result = $this->qb->resolve([
            'source'       => 'categories',
            'filter_logic' => 'or',
            'filters'      => [
                ['field' => 'slug', 'op' => '=', 'value' => 'alpha'],
                ['field' => 'slug', 'op' => '=', 'value' => 'beta'],
            ],
        ]);

        $this->assertCount(2, $result['items']);
        $slugs = array_column($result['items'], 'slug');
        $this->assertContains('alpha', $slugs);
        $this->assertContains('beta', $slugs);
        $this->assertNotContains('gamma', $slugs);
    }

    public function test_filter_logic_and_requires_all_conditions(): void
    {
        Category::factory()->create(['name' => 'Alpha', 'slug' => 'alpha']);
        Category::factory()->create(['name' => 'Beta',  'slug' => 'beta']);

        $result = $this->qb->resolve([
            'source'       => 'categories',
            'filter_logic' => 'and',
            'filters'      => [
                ['field' => 'slug', 'op' => '=', 'value' => 'alpha'],
                ['field' => 'slug', 'op' => '=', 'value' => 'beta'],
            ],
        ]);

        $this->assertCount(0, $result['items']);
    }

    public function test_filter_logic_defaults_to_and(): void
    {
        Category::factory()->create(['name' => 'Alpha', 'slug' => 'alpha']);
        Category::factory()->create(['name' => 'Beta',  'slug' => 'beta']);

        // No filter_logic key — should behave like AND
        $result = $this->qb->resolve([
            'source'  => 'categories',
            'filters' => [
                ['field' => 'slug', 'op' => '=', 'value' => 'alpha'],
                ['field' => 'slug', 'op' => '=', 'value' => 'beta'],
            ],
        ]);

        $this->assertCount(0, $result['items']);
    }
}
