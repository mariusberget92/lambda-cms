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
            'Default Header',
            'Default Footer',
            'Post Card',
        ])->update(['is_system' => true]);

        // Definitions with lazy block builders (closures) so that templates referencing
        // the Post Card partial can resolve its DB id AFTER it has been inserted.
        $definitions = [
            ['type' => 'partial',        'title' => 'Post Card',              'loop_source' => 'posts', 'blocks' => fn () => $this->postCardBlocks()],
            ['type' => 'header',         'title' => 'Default Header',         'blocks' => fn () => $this->headerBlocks()],
            ['type' => 'footer',         'title' => 'Default Footer',         'blocks' => fn () => $this->footerBlocks()],
            ['type' => 'blog-index',     'title' => 'Default Blog Index',     'blocks' => fn () => $this->blogIndexBlocks()],
            ['type' => 'single-post',    'title' => 'Default Single Post',    'blocks' => fn () => $this->singlePostBlocks()],
            ['type' => 'archive',        'title' => 'Default Archive',        'blocks' => fn () => $this->archiveBlocks()],
            ['type' => 'search-results', 'title' => 'Default Search Results', 'blocks' => fn () => $this->searchBlocks()],
        ];

        foreach ($definitions as $def) {
            $existing = Template::where('type', $def['type'])
                ->where('title', $def['title'])
                ->first();

            // Resolve blocks lazily so postCardTemplateId() can find the Post Card row
            $blocks = is_callable($def['blocks']) ? ($def['blocks'])() : $def['blocks'];

            if ($existing) {
                // Force-update system template blocks to keep design current
                $existing->update([
                    'is_system'   => true,
                    'blocks'      => $blocks,
                    'loop_source' => $def['loop_source'] ?? $existing->loop_source,
                ]);
                continue;
            }

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

    private function headerBlocks(): array
    {
        return [
            $this->block(400, 'nav-header', [
                'logoText'   => '',
                'showSearch' => true,
                'sticky'     => true,
            ]),
        ];
    }

    private function footerBlocks(): array
    {
        return [
            $this->block(410, 'site-footer', [
                'tagline'   => '',
                'copyright' => '',
                'showRss'   => true,
                'columns'   => [
                    ['heading' => 'Content', 'links' => [
                        ['label' => 'Home',     'url' => '/'],
                        ['label' => 'RSS Feed', 'url' => '/feed'],
                    ]],
                ],
            ]),
        ];
    }

    private function blogIndexBlocks(): array
    {
        return [
            // Hero masthead — dark full-width panel with title and subtitle
            $this->section(40, ['paddingY' => ['default' => 0], 'paddingX' => ['default' => 0], 'fullWidth' => true, 'minHeight' => 'auto'], [
                $this->block(41, 'masthead', [
                    'eyebrow'  => 'Open Source Blog',
                    'title'    => 'Share your ideas ||with the world||',
                    'subtitle' => 'A fast, beautiful blog built on Lambda CMS. Write, publish, and connect with your audience.',
                    'stats'    => [],
                ]),
            ]),

            $this->section(1, [
                'paddingY'  => ['default' => 10],
                'paddingX'  => ['default' => 0],
                'fullWidth' => true,
                'minHeight' => 'auto',
            ], [
                // Outer flex-row container — splits into main + sidebar
                $this->block(2, 'container', [
                    'mode'      => 'flex',
                    'direction' => 'row',
                    'wrap'      => false,
                    'gap'       => '2.5rem',
                    'padding'   => 0,
                    'align'     => 'start',
                    'maxWidth'  => 'full',
                ], [
                    // ── Main column (flex: 3) ────────────────────────────
                    $this->block(3, 'container', [
                        'mode'      => 'flex',
                        'direction' => 'column',
                        'gap'       => '1.25rem',
                        'padding'   => 0,
                        'maxWidth'  => 'full',
                    ], [
                        $this->block(4, 'active-filter', ['defaultTitle' => 'Latest Posts']),
                        $this->block(5, 'loop', [
                            'source'       => 'posts',
                            'filters'      => [
                                ['field' => 'category_slug', 'op' => '=', 'urlParam' => 'category'],
                                ['field' => 'tag_slug',      'op' => '=', 'urlParam' => 'tag'],
                            ],
                            'filter_logic' => 'and',
                            'sort'         => ['field' => 'published_at', 'direction' => 'desc'],
                            'limit'        => 8,
                            'columns'      => 2,
                            'gap'          => 'lg',
                            'pageParam'    => 'page',
                        ], [$this->templateBlock(10, $this->postCardTemplateId() ?? 0)]),
                        $this->block(6, 'pagination', [
                            'pageParam'   => 'page',
                            'style'       => 'numbered',
                            'alignment'   => 'center',
                            'buttonStyle' => 'outline',
                        ]),
                    ], [], '', 'flex:3;min-width:0'),

                    // ── Sidebar column (flex: 1) ─────────────────────────
                    $this->block(20, 'container', [
                        'mode'      => 'flex',
                        'direction' => 'column',
                        'align'     => 'stretch',
                        'gap'       => '1.25rem',
                        'padding'   => 0,
                        'maxWidth'  => 'full',
                    ], [
                        // Search widget (SearchBlock renders its own card)
                        $this->block(21, 'search', [
                            'placeholder' => 'Search posts…',
                            'buttonLabel' => 'Search',
                            'scope'       => 'posts',
                        ]),

                        // Categories card
                        $this->block(36, 'container', [
                            'mode'      => 'flex',
                            'direction' => 'column',
                            'gap'       => '0.25rem',
                            'padding'   => 0,
                            'maxWidth'  => 'full',
                        ], [
                            $this->block(22, 'heading', ['level' => 3, 'text' => 'Categories']),
                            $this->block(23, 'loop', [
                                'source'  => 'categories',
                                'filters' => [],
                                'sort'    => ['field' => 'posts_count', 'direction' => 'desc'],
                                'limit'   => 20,
                                'columns' => 1,
                                'gap'     => 0,
                            ], [
                                $this->block(30, 'filter-link',
                                    ['paramName' => 'category', 'label' => '', 'variant' => 'list'],
                                    [], ['label' => 'loop:name']
                                ),
                            ]),
                        ], [], 'sidebar-card', ''),

                        // Tags card
                        $this->block(37, 'container', [
                            'mode'      => 'flex',
                            'direction' => 'column',
                            'gap'       => '0.75rem',
                            'padding'   => 0,
                            'maxWidth'  => 'full',
                        ], [
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
                                $this->block(31, 'filter-link',
                                    ['paramName' => 'tag', 'label' => '', 'variant' => 'pill'],
                                    [], ['label' => 'loop:name']
                                ),
                            ]),
                        ], [], 'sidebar-card', ''),
                    ], [], '', 'flex:1;min-width:0'),
                ]),
            ]),
        ];
    }

    private function singlePostBlocks(): array
    {
        return [
            // Hero image — full-bleed, no padding, cinematic 21:9 with gradient fade
            $this->section(110, ['paddingY' => ['default' => 0], 'paddingX' => ['default' => 0], 'fullWidth' => true, 'minHeight' => 'auto'], [
                $this->block(111, 'post-featured-image', ['variant' => 'hero', 'aspectRatio' => '21/9', 'maxWidth' => '100%']),
            ]),

            // Content — constrained readable width
            $this->section(112, ['paddingY' => ['default' => 10], 'paddingX' => ['default' => 4], 'fullWidth' => false, 'innerMaxWidth' => '2xl', 'minHeight' => 'auto'], [
                $this->block(113, 'post-title', ['tag' => 'h1']),

                // Unified white card: Written by + Topics + body with token-based dividers
                $this->block(120, 'container', [
                    'mode'      => 'flex',
                    'direction' => 'column',
                    'gap'       => '0',
                    'padding'   => 0,
                    'maxWidth'  => 'full',
                ], [
                    $this->block(114, 'post-meta',     ['showDate' => true, 'showAuthor' => true, 'showReadTime' => true]),
                    $this->block(115, 'post-taxonomy', ['showCategories' => true, 'showTags' => true]),
                    $this->block(117, 'post-body',     []),
                ], [], 'post-content-card', ''),

                $this->block(118, 'divider', []),
                $this->block(119, 'post-comments', []),
            ]),
        ];
    }

    private function archiveBlocks(): array
    {
        return [
            $this->section(200, ['paddingY' => ['default' => 10], 'paddingX' => ['default' => 0], 'fullWidth' => true, 'minHeight' => 'auto'], [
                $this->block(201, 'archive-title', ['tag' => 'h1']),
                $this->block(202, 'loop', [
                    'source'  => 'posts',
                    'filters' => [],
                    'sort'    => ['field' => 'published_at', 'direction' => 'desc'],
                    'limit'   => 8,
                    'columns' => 2,
                    'gap'     => 'lg',
                ], [$this->templateBlock(210, $this->postCardTemplateId() ?? 0)]),
            ]),
        ];
    }

    private function searchBlocks(): array
    {
        return [
            $this->section(300, ['paddingY' => ['default' => 10], 'paddingX' => ['default' => 0], 'fullWidth' => true, 'minHeight' => 'auto'], [
                // Search banner — design token card
                $this->block(304, 'container', [
                    'mode'      => 'flex',
                    'direction' => 'column',
                    'gap'       => '1rem',
                    'padding'   => 0,
                    'maxWidth'  => 'full',
                ], [
                    $this->block(311, 'heading', ['level' => 1, 'text' => 'Search']),
                    $this->block(302, 'search', ['placeholder' => 'Search posts…', 'buttonLabel' => 'Search', 'scope' => 'posts']),
                ], [], 'sidebar-card', ''),
                $this->block(303, 'loop', [
                    'source'  => 'posts',
                    'filters' => [['field' => 'title', 'op' => 'contains', 'urlParam' => 'q', 'value' => '']],
                    'sort'    => ['field' => 'published_at', 'direction' => 'desc'],
                    'limit'   => 10,
                    'columns' => 2,
                    'gap'     => 'lg',
                ], [$this->templateBlock(310, $this->postCardTemplateId() ?? 0)]),
            ]),
        ];
    }

    // ── Post Card partial ─────────────────────────────────────────────────────

    private function postCardBlocks(): array
    {
        return [
            $this->block(500, 'post-card', []),
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
