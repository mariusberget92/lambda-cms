<?php

namespace Database\Seeders;

use App\Models\Template;
use App\Models\User;
use Illuminate\Database\Seeder;

class TemplateSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::role('administrator')->first();
        if (!$admin) return;

        $definitions = [
            ['type' => 'blog-index',     'title' => 'Default Blog Index',     'blocks' => $this->blogIndexBlocks()],
            ['type' => 'single-post',    'title' => 'Default Single Post',    'blocks' => $this->singlePostBlocks()],
            ['type' => 'archive',        'title' => 'Default Archive',        'blocks' => $this->archiveBlocks()],
            ['type' => 'search-results', 'title' => 'Default Search Results', 'blocks' => $this->searchBlocks()],
        ];

        foreach ($definitions as $def) {
            if (Template::where('type', $def['type'])->where('status', 'published')->exists()) {
                continue;
            }
            Template::create([
                'user_id' => $admin->id,
                'type'    => $def['type'],
                'title'   => $def['title'],
                'status'  => 'published',
                'blocks'  => $def['blocks'],
            ]);
        }
    }

    // ── Template block definitions ────────────────────────────────────────────

    private function blogIndexBlocks(): array
    {
        return [
            $this->section(1, ['paddingY' => ['default' => 16], 'paddingX' => ['default' => 8], 'fullWidth' => true, 'minHeight' => 'auto'], [
                $this->block(2, 'heading', ['level' => 2, 'text' => 'Latest Posts']),
                $this->block(3, 'loop', [
                    'source'  => 'posts',
                    'filters' => [],
                    'sort'    => ['field' => 'published_at', 'direction' => 'desc'],
                    'limit'   => 9,
                    'columns' => 3,
                    'gap'     => 'md',
                ], [$this->postCard(10)]),
            ]),
        ];
    }

    private function singlePostBlocks(): array
    {
        return [
            $this->section(100, ['paddingY' => ['default' => 16], 'paddingX' => ['default' => 4], 'fullWidth' => false, 'innerMaxWidth' => '2xl', 'minHeight' => 'auto'], [
                $this->block(101, 'post-featured-image', ['aspectRatio' => '16/9', 'maxWidth' => '100%']),
                $this->block(102, 'post-title',    ['tag' => 'h1']),
                $this->block(103, 'post-meta',     ['showDate' => true, 'showAuthor' => true, 'showReadTime' => true]),
                $this->block(104, 'post-taxonomy', ['showCategories' => true, 'showTags' => true]),
                $this->block(105, 'divider',       []),
                $this->block(106, 'post-body',     []),
                $this->block(107, 'divider',       []),
                $this->block(108, 'post-comments', []),
            ]),
        ];
    }

    private function archiveBlocks(): array
    {
        return [
            $this->section(200, ['paddingY' => ['default' => 16], 'paddingX' => ['default' => 8], 'fullWidth' => true, 'minHeight' => 'auto'], [
                $this->block(201, 'archive-title', ['tag' => 'h1']),
                $this->block(202, 'loop', [
                    'source'  => 'posts',
                    'filters' => [],
                    'sort'    => ['field' => 'published_at', 'direction' => 'desc'],
                    'limit'   => 9,
                    'columns' => 3,
                    'gap'     => 'md',
                ], [$this->postCard(210)]),
            ]),
        ];
    }

    private function searchBlocks(): array
    {
        return [
            $this->section(300, ['paddingY' => ['default' => 16], 'paddingX' => ['default' => 8], 'fullWidth' => true, 'minHeight' => 'auto'], [
                $this->block(301, 'heading', ['level' => 2, 'text' => 'Search']),
                $this->block(302, 'search',  ['placeholder' => 'Search posts…', 'buttonLabel' => 'Search']),
                $this->block(303, 'loop', [
                    'source'  => 'posts',
                    'filters' => [['field' => 'title', 'op' => 'contains', 'urlParam' => 'q', 'value' => '']],
                    'sort'    => ['field' => 'published_at', 'direction' => 'desc'],
                    'limit'   => 10,
                    'columns' => 1,
                    'gap'     => 'md',
                ], [$this->postCard(310)]),
            ]),
        ];
    }

    // ── Shared card ───────────────────────────────────────────────────────────

    /**
     * A minimal post card for use inside a loop block.
     * Uses IDs: $baseId, $baseId+1, $baseId+2, $baseId+3
     */
    private function postCard(int $baseId): array
    {
        return $this->block(
            $baseId, 'container',
            ['mode' => 'flex', 'direction' => 'column', 'gap' => 3, 'padding' => 4, 'maxWidth' => 'full'],
            [
                $this->block($baseId + 1, 'heading',
                    ['level' => 3, 'text' => ''],
                    [], ['text' => 'loop:title']
                ),
                $this->block($baseId + 2, 'paragraph',
                    ['content' => ''],
                    [], ['content' => 'loop:excerpt']
                ),
                $this->block($baseId + 3, 'link',
                    ['label' => 'Read more →', 'url' => '#', 'target' => '_self'],
                    [], ['url' => 'loop:url']
                ),
            ],
            [], 'rounded-xl border bg-card'
        );
    }

    // ── Block builder helpers ─────────────────────────────────────────────────

    private function section(int $id, array $data, array $children): array
    {
        return ['id' => $id, 'type' => 'section', 'data' => $data, 'children' => $children];
    }

    /**
     * Build a generic block array.
     *
     * @param  int     $id
     * @param  string  $type
     * @param  array   $data
     * @param  array   $children    nested blocks (container/section/loop only)
     * @param  array   $bindings    dynamic field bindings (e.g. ['text' => 'loop:title'])
     * @param  string  $customClasses  Tailwind classes applied to the BlockRenderer wrapper div
     */
    private function block(int $id, string $type, array $data, array $children = [], array $bindings = [], string $customClasses = ''): array
    {
        $b = ['id' => $id, 'type' => $type, 'data' => $data];
        if (!empty($children))     $b['children']     = $children;
        if (!empty($bindings))     $b['bindings']      = $bindings;
        if ($customClasses !== '') $b['customClasses'] = $customClasses;
        return $b;
    }
}
