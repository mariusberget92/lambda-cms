<?php

namespace Database\Factories;

use App\Models\Page;
use Illuminate\Database\Eloquent\Factories\Factory;

class NavItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'type'       => 'custom',
            'label'      => $this->faker->words(2, true),
            'url'        => '/' . $this->faker->slug(),
            'page_id'    => null,
            'sort_order' => $this->faker->numberBetween(0, 100),
        ];
    }

    public function forPage(?Page $page = null): static
    {
        return $this->state(function () use ($page) {
            $page ??= Page::factory()->published()->create();

            return [
                'type'    => 'page',
                'label'   => $page->title,
                'url'     => null,
                'page_id' => $page->id,
            ];
        });
    }
}
