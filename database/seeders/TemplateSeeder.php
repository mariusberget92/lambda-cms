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

        // Retroactively mark any already-seeded system templates
        Template::whereIn('title', [
            'Default Blog Index',
            'Default Single Post',
            'Default Archive',
            'Default Search Results',
            'Post Card',
        ])->update(['is_system' => true]);

        // Definitions with lazy block builders (closures) so that templates referencing
        // the Post Card partial can resolve its DB id AFTER it has been inserted.
        $definitions = [
            ['type' => 'partial',        'title' => 'Post Card',              'loop_source' => 'posts', 'blocks' => fn () => $this->postCardBlocks()],
            ['type' => 'blog-index',     'title' => 'Default Blog Index',     'blocks' => fn () => $this->blogIndexBlocks()],
            ['type' => 'single-post',    'title' => 'Default Single Post',    'blocks' => fn () => $this->singlePostBlocks()],
            ['type' => 'archive',        'title' => 'Default Archive',        'blocks' => fn () => $this->archiveBlocks()],
            ['type' => 'search-results', 'title' => 'Default Search Results', 'blocks' => fn () => $this->searchBlocks()],
        ];

        foreach ($definitions as $def) {
            $existing = Template::where('type', $def['type'])
                ->where('title', $def['title'])
                ->first();

            if ($existing) {
                $existing->update(['is_system' => true]);
                continue;
            }

            // Resolve blocks lazily so postCardTemplateId() can find the Post Card row
            $blocks = is_callable($def['blocks']) ? ($def['blocks'])() : $def['blocks'];

            Template::create([
                'user_id'     => $admin->id,
                'type'        => $def['type'],
                'title'       => $def['title'],
                'loop_source' => $def['loop_source'] ?? null,
                'status'      => 'published',
                'is_system'   => true,
                'blocks'      => $blocks,
            ]);
        }
    }

    // ── Template block definitions ────────────────────────────────────────────

    private function blogIndexBlocks(): array
    {
        return [
            $this->section(1, [
                'paddingY'  => ['default' => 16],
                'paddingX'  => ['default' => 8],
                'fullWidth' => true,
                'minHeight' => 'auto',
            ], [
                // Outer flex-row container — splits into main + sidebar
                $this->block(2, 'container', [
                    'mode'      => 'flex',
                    'direction' => 'row',
                    'wrap'      => false,
                    'gap'       => '2rem',
                    'padding'   => 0,
                    'align'     => 'start',
                    'maxWidth'  => 'full',
                ], [
                    // ── Main column (flex: 3) ────────────────────────────
                    $this->block(3, 'container', [
                        'mode'      => 'flex',
                        'direction' => 'column',
                        'gap'       => '1.5rem',
                        'padding'   => 0,
                        'maxWidth'  => 'full',
                    ], [
                        $this->block(4, 'heading', ['level' => 2, 'text' => 'Latest Posts']),
                        $this->block(5, 'loop', [
                            'source'       => 'posts',
                            'filters'      => [
                                ['field' => 'category_slug', 'op' => '=', 'urlParam' => 'category'],
                                ['field' => 'tag_slug',      'op' => '=', 'urlParam' => 'tag'],
                            ],
                            'filter_logic' => 'and',
                            'sort'         => ['field' => 'published_at', 'direction' => 'desc'],
                            'limit'        => 9,
                            'columns'      => 1,
                            'gap'          => 'md',
                        ], [$this->templateBlock(10, $this->postCardTemplateId() ?? 0)]),
                    ], [], '', 'flex:3;min-width:0'),

                    // ── Sidebar column (flex: 1) ─────────────────────────
                    $this->block(20, 'container', [
                        'mode'      => 'flex',
                        'direction' => 'column',
                        'gap'       => '1rem',
                        'padding'   => 0,
                        'maxWidth'  => 'full',
                    ], [
                        $this->block(21, 'search', [
                            'placeholder' => 'Search posts…',
                            'buttonLabel' => 'Search',
                            'scope'       => 'posts',
                        ]),
                        $this->block(22, 'heading', ['level' => 3, 'text' => 'Categories']),
                        $this->block(23, 'loop', [
                            'source'  => 'categories',
                            'filters' => [],
                            'sort'    => ['field' => 'posts_count', 'direction' => 'desc'],
                            'limit'   => 20,
                            'columns' => 1,
                            'gap'     => 'sm',
                        ], [
                            // Each category wrapped in a card-style container
                            $this->block(30, 'container', [
                                'mode'      => 'flex',
                                'direction' => 'row',
                                'gap'       => 0,
                                'padding'   => 0,
                                'maxWidth'  => 'full',
                            ], [
                                $this->block(32, 'filter-link',
                                    ['paramName' => 'category', 'label' => ''],
                                    [], ['label' => 'loop:name'],
                                    'w-full'
                                ),
                            ], [], 'rounded-lg border bg-card shadow-sm overflow-hidden'),
                        ]),
                        $this->block(24, 'heading', ['level' => 3, 'text' => 'Tags']),
                        $this->block(25, 'loop', [
                            'source'   => 'tags',
                            'filters'  => [],
                            'sort'     => ['field' => 'posts_count', 'direction' => 'desc'],
                            'limit'    => 30,
                            'columns'  => 'flex',
                            'flexWrap' => true,
                            'gap'      => 'sm',
                        ], [
                            // Tag as a pill
                            $this->block(31, 'filter-link',
                                ['paramName' => 'tag', 'label' => ''],
                                [], ['label' => 'loop:name'],
                                'inline-flex items-center rounded-full border px-3 py-1 text-xs font-medium bg-muted hover:bg-accent transition-colors cursor-pointer'
                            ),
                        ]),
                    ], [], '', 'flex:1;min-width:0'),
                ]),
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
                    'columns' => 1,
                    'gap'     => 'md',
                ], [$this->templateBlock(210, $this->postCardTemplateId() ?? 0)]),
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
                ], [$this->templateBlock(310, $this->postCardTemplateId() ?? 0)]),
            ]),
        ];
    }

    // ── Post Card partial ─────────────────────────────────────────────────────

    private function postCardBlocks(): array
    {
        return [
            $this->block(
                500, 'container',
                [
                    'mode'      => 'flex',
                    'direction' => 'column',
                    'gap'       => 0,
                    'padding'   => 0,
                    'maxWidth'  => 'full',
                ],
                [
                    // Featured image — bound to loop:featured_image_url, aspect-video, no margin
                    $this->block(501, 'image',
                        ['url' => '', 'alt' => '', 'aspectRatio' => '16/9'],
                        [], ['url' => 'loop:featured_image_url', 'alt' => 'loop:title']
                    ),

                    // Inner content area — tighter padding, left-aligned text
                    $this->block(502, 'container',
                        [
                            'mode'      => 'flex',
                            'direction' => 'column',
                            'gap'       => '0.5rem',
                            'padding'   => 4,
                            'maxWidth'  => 'full',
                        ],
                        [
                            // Title
                            $this->block(503, 'heading',
                                ['level' => 3, 'text' => ''],
                                [], ['text' => 'loop:title'],
                                'text-left'
                            ),

                            // Excerpt
                            $this->block(504, 'paragraph',
                                ['content' => ''],
                                [], ['content' => 'loop:excerpt'],
                                'line-clamp-2 text-sm text-muted-foreground text-left'
                            ),

                            // Meta + Read more on the same row
                            $this->block(507, 'container',
                                [
                                    'mode'      => 'flex',
                                    'direction' => 'row',
                                    'gap'       => '0.5rem',
                                    'padding'   => 0,
                                    'maxWidth'  => 'full',
                                    'align'     => 'center',
                                    'justify'   => 'between',
                                ],
                                [
                                    // Published date (human-readable)
                                    $this->block(505, 'paragraph',
                                        ['content' => ''],
                                        [], ['content' => 'loop:published_at_formatted'],
                                        'text-xs text-muted-foreground/70'
                                    ),

                                    // Read more link
                                    $this->block(506, 'link',
                                        ['label' => 'Read more →', 'url' => '#', 'target' => '_self'],
                                        [], ['url' => 'loop:url'],
                                        'text-sm font-medium text-primary hover:underline shrink-0'
                                    ),
                                ]
                            ),
                        ]
                    ),
                ],
                [], 'rounded-xl shadow-md overflow-hidden bg-card',
                'font-family: Inter, sans-serif;'
            ),
        ];
    }

    // ── Template reference helpers ─────────────────────────────────────────────

    private function postCardTemplateId(): ?int
    {
        return \App\Models\Template::where('title', 'Post Card')
            ->where('type', 'partial')
            ->value('id');
    }

    private function templateBlock(int $id, int $templateId): array
    {
        return $this->block($id, 'template', ['template_id' => $templateId]);
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
     * @param  array   $children       nested blocks (container/section/loop only)
     * @param  array   $bindings       dynamic field bindings (e.g. ['text' => 'loop:title'])
     * @param  string  $customClasses  Tailwind classes applied to the BlockRenderer wrapper div
     * @param  string  $customCss      Inline CSS applied via <style>#block-{id} { … }</style>
     */
    private function block(int $id, string $type, array $data, array $children = [], array $bindings = [], string $customClasses = '', string $customCss = ''): array
    {
        $b = ['id' => $id, 'type' => $type, 'data' => $data];
        if (!empty($children))     $b['children']     = $children;
        if (!empty($bindings))     $b['bindings']      = $bindings;
        if ($customClasses !== '') $b['customClasses'] = $customClasses;
        if ($customCss     !== '') $b['customCss']     = $customCss;
        return $b;
    }
}
