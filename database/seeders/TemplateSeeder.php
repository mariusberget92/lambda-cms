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
        if (! $admin) {
            return;
        }

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
                    'is_system' => true,
                    'blocks' => $blocks,
                    'loop_source' => $def['loop_source'] ?? $existing->loop_source,
                ]);

                continue;
            }

            Template::create([
                'user_id' => $admin->id,
                'type' => $def['type'],
                'title' => $def['title'],
                'loop_source' => $def['loop_source'] ?? null,
                'status' => 'published',
                'is_system' => true,
                'blocks' => $blocks,
            ]);
        }
    }

    // ── Template block definitions ────────────────────────────────────────────

    private function headerBlocks(): array
    {
        return [
            $this->block(400, 'nav-header', [
                'logoText' => '',
                'showSearch' => true,
                'sticky' => true,
            ]),
        ];
    }

    private function footerBlocks(): array
    {
        return [
            $this->block(410, 'site-footer', [
                'tagline' => '',
                'copyright' => '',
                'showRss' => true,
                'columns' => [
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
        $postCardId = $this->postCardTemplateId() ?? 0;

        return [
            // ── 1. Hero masthead with stats ──────────────────────────────
            $this->section(40, ['paddingY' => ['default' => 0], 'paddingX' => ['default' => 0], 'fullWidth' => true, 'minHeight' => 'auto'], [
                $this->block(41, 'masthead', [
                    'eyebrow' => 'Lambda CMS',
                    'title' => 'Build something ||remarkable||',
                    'subtitle' => 'An open-source CMS with a visual block editor, built-in CRM, and email campaigns. Everything you need to publish, engage, and grow.',
                    'stats' => [
                        ['value' => '47+', 'label' => 'Block Types'],
                        ['value' => '∞', 'label' => 'Templates'],
                        ['value' => '0', 'label' => 'Vendor Lock-in'],
                    ],
                ]),
            ]),

            // ── 2. Features — 3 icon-list cards in a grid ────────────────
            $this->section(50, ['paddingY' => ['default' => 8], 'paddingX' => ['default' => 4], 'fullWidth' => false, 'innerMaxWidth' => 'full', 'minHeight' => 'auto'], [
                $this->block(51, 'heading', ['level' => 2, 'text' => 'Everything is a block']),
                $this->block(52, 'paragraph', ['content' => 'Every section on this page — including this one — was built with the visual block editor. What you see is what your users can create.']),
                $this->block(53, 'spacer', ['height' => '1.5rem']),
                $this->block(54, 'container', [
                    'mode' => 'flex',
                    'direction' => 'row',
                    'wrap' => true,
                    'gap' => '1.5rem',
                    'padding' => 0,
                    'maxWidth' => 'full',
                ], [
                    // Feature card 1: Content
                    $this->block(55, 'container', [
                        'mode' => 'flex',
                        'direction' => 'column',
                        'gap' => '0.75rem',
                        'padding' => 0,
                        'maxWidth' => 'full',
                    ], [
                        $this->block(56, 'heading', ['level' => 3, 'text' => 'Content Management']),
                        $this->block(57, 'icon-list', [
                            'direction' => 'vertical',
                            'gap' => '0.5rem',
                            'iconSize' => '1.1em',
                            'iconColor' => 'var(--accent)',
                            'items' => [
                                ['icon' => 'lucide:file-text', 'text' => 'Rich text & markdown posts'],
                                ['icon' => 'lucide:layout-grid', 'text' => 'Visual block editor with 47+ blocks'],
                                ['icon' => 'lucide:image', 'text' => 'Drag-and-drop media library'],
                                ['icon' => 'lucide:tags', 'text' => 'Categories, tags & taxonomies'],
                            ],
                        ]),
                    ], [], 'sidebar-card', 'flex:1;min-width:280px'),

                    // Feature card 2: CRM
                    $this->block(60, 'container', [
                        'mode' => 'flex',
                        'direction' => 'column',
                        'gap' => '0.75rem',
                        'padding' => 0,
                        'maxWidth' => 'full',
                    ], [
                        $this->block(61, 'heading', ['level' => 3, 'text' => 'Built-in CRM']),
                        $this->block(62, 'icon-list', [
                            'direction' => 'vertical',
                            'gap' => '0.5rem',
                            'iconSize' => '1.1em',
                            'iconColor' => 'var(--accent)',
                            'items' => [
                                ['icon' => 'lucide:users', 'text' => 'Contacts & companies'],
                                ['icon' => 'lucide:kanban', 'text' => 'Deal pipeline with drag-and-drop'],
                                ['icon' => 'lucide:phone', 'text' => 'Call lists with work mode'],
                                ['icon' => 'lucide:activity', 'text' => 'Activity timeline & notes'],
                            ],
                        ]),
                    ], [], 'sidebar-card', 'flex:1;min-width:280px'),

                    // Feature card 3: Email
                    $this->block(65, 'container', [
                        'mode' => 'flex',
                        'direction' => 'column',
                        'gap' => '0.75rem',
                        'padding' => 0,
                        'maxWidth' => 'full',
                    ], [
                        $this->block(66, 'heading', ['level' => 3, 'text' => 'Email Campaigns']),
                        $this->block(67, 'icon-list', [
                            'direction' => 'vertical',
                            'gap' => '0.5rem',
                            'iconSize' => '1.1em',
                            'iconColor' => 'var(--accent)',
                            'items' => [
                                ['icon' => 'lucide:mail', 'text' => 'Customizable email templates'],
                                ['icon' => 'lucide:send', 'text' => 'Campaign builder & scheduling'],
                                ['icon' => 'lucide:user-plus', 'text' => 'Subscriber management'],
                                ['icon' => 'lucide:bar-chart-3', 'text' => 'Delivery tracking & reports'],
                            ],
                        ]),
                    ], [], 'sidebar-card', 'flex:1;min-width:280px'),
                ]),
            ]),

            // ── 3. Stat cards row ────────────────────────────────────────
            $this->section(70, ['paddingY' => ['default' => 8], 'paddingX' => ['default' => 4], 'fullWidth' => false, 'innerMaxWidth' => 'full', 'minHeight' => 'auto'], [
                $this->block(71, 'container', [
                    'mode' => 'flex',
                    'direction' => 'row',
                    'wrap' => true,
                    'gap' => '1.5rem',
                    'padding' => 0,
                    'maxWidth' => 'full',
                ], [
                    $this->block(72, 'stat-card', ['value' => '47+', 'label' => 'Block Types', 'trend' => 'Sections, loops, tabs, accordions & more', 'trendTone' => 'neutral'], [], [], '', 'flex:1;min-width:200px'),
                    $this->block(73, 'stat-card', ['value' => '7', 'label' => 'Template Types', 'trend' => 'Blog, post, archive, header, footer, partial, search', 'trendTone' => 'neutral'], [], [], '', 'flex:1;min-width:200px'),
                    $this->block(74, 'stat-card', ['value' => '42', 'label' => 'Permissions', 'trend' => 'Granular role-based access control', 'trendTone' => 'neutral'], [], [], '', 'flex:1;min-width:200px'),
                    $this->block(75, 'stat-card', ['value' => 'REST', 'label' => 'API', 'trend' => 'Full headless API with authentication', 'trendTone' => 'neutral'], [], [], '', 'flex:1;min-width:200px'),
                ]),
            ]),

            // ── 4. Latest posts + sidebar ────────────────────────────────
            $this->section(1, ['paddingY' => ['default' => 6], 'paddingX' => ['default' => 0], 'fullWidth' => true, 'minHeight' => 'auto'], [
                $this->block(2, 'container', [
                    'mode' => 'flex',
                    'direction' => 'row',
                    'wrap' => false,
                    'gap' => '2.5rem',
                    'padding' => 0,
                    'align' => 'start',
                    'maxWidth' => 'full',
                ], [
                    // Main column
                    $this->block(3, 'container', [
                        'mode' => 'flex',
                        'direction' => 'column',
                        'gap' => '1.25rem',
                        'padding' => 0,
                        'maxWidth' => 'full',
                    ], [
                        $this->block(4, 'active-filter', ['defaultTitle' => 'Latest Posts']),
                        $this->block(5, 'loop', [
                            'source' => 'posts',
                            'filters' => [
                                ['field' => 'category_slug', 'op' => '=', 'urlParam' => 'category'],
                                ['field' => 'tag_slug', 'op' => '=', 'urlParam' => 'tag'],
                            ],
                            'filter_logic' => 'and',
                            'sort' => ['field' => 'published_at', 'direction' => 'desc'],
                            'limit' => 8,
                            'columns' => 2,
                            'gap' => 'lg',
                            'pageParam' => 'page',
                        ], [$this->templateBlock(10, $postCardId)]),
                        $this->block(6, 'pagination', [
                            'pageParam' => 'page',
                            'style' => 'numbered',
                            'alignment' => 'center',
                            'buttonStyle' => 'outline',
                        ]),
                    ], [], '', 'flex:3;min-width:0'),

                    // Sidebar
                    $this->block(20, 'container', [
                        'mode' => 'flex',
                        'direction' => 'column',
                        'align' => 'stretch',
                        'gap' => '1.25rem',
                        'padding' => 0,
                        'maxWidth' => 'full',
                    ], [
                        $this->block(21, 'search', [
                            'placeholder' => 'Search posts…',
                            'buttonLabel' => 'Search',
                            'scope' => 'posts',
                        ]),
                        $this->block(36, 'container', [
                            'mode' => 'flex',
                            'direction' => 'column',
                            'gap' => '0.25rem',
                            'padding' => 0,
                            'maxWidth' => 'full',
                        ], [
                            $this->block(22, 'heading', ['level' => 3, 'text' => 'Categories']),
                            $this->block(23, 'loop', [
                                'source' => 'categories',
                                'filters' => [],
                                'sort' => ['field' => 'posts_count', 'direction' => 'desc'],
                                'limit' => 20,
                                'columns' => 1,
                                'gap' => 0,
                            ], [
                                $this->block(30, 'filter-link',
                                    ['paramName' => 'category', 'label' => '', 'variant' => 'list'],
                                    [], ['label' => 'loop:name']
                                ),
                            ]),
                        ], [], 'sidebar-card', ''),
                        $this->block(37, 'container', [
                            'mode' => 'flex',
                            'direction' => 'column',
                            'gap' => '0.75rem',
                            'padding' => 0,
                            'maxWidth' => 'full',
                        ], [
                            $this->block(24, 'heading', ['level' => 3, 'text' => 'Tags']),
                            $this->block(25, 'loop', [
                                'source' => 'tags',
                                'filters' => [],
                                'sort' => ['field' => 'posts_count', 'direction' => 'desc'],
                                'limit' => 30,
                                'columns' => 'flex',
                                'flexWrap' => true,
                                'gap' => 'sm',
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

            // ── 5. Testimonial quote ─────────────────────────────────────
            $this->section(80, ['paddingY' => ['default' => 8], 'paddingX' => ['default' => 4], 'fullWidth' => false, 'innerMaxWidth' => '2xl', 'minHeight' => 'auto'], [
                $this->block(81, 'quote', [
                    'text' => 'Lambda CMS gave us a publishing workflow that feels like it was built for our team. The block editor is incredibly flexible, and having CRM and email built in means we don\'t need five different SaaS subscriptions.',
                    'attribution' => 'A happy Lambda CMS user',
                ]),
            ]),

            // ── 6. FAQ accordion ─────────────────────────────────────────
            $this->section(90, ['paddingY' => ['default' => 8], 'paddingX' => ['default' => 4], 'fullWidth' => false, 'innerMaxWidth' => '2xl', 'minHeight' => 'auto'], [
                $this->block(91, 'heading', ['level' => 2, 'text' => 'Frequently Asked Questions']),
                $this->block(92, 'spacer', ['height' => '0.5rem']),
                $this->block(93, 'accordion', [
                    'defaultState' => 'first-open',
                    'borderStyle' => 'separated',
                ], [
                    ['id' => 94, 'type' => 'accordion-item', 'data' => ['title' => 'What is the block editor?'], 'children' => [
                        $this->block(95, 'paragraph', ['content' => 'The block editor is a visual page builder with 47+ block types. You can create complex layouts by combining sections, containers, loops, tabs, accordions, and content blocks — without writing any code. This entire landing page was built with it.']),
                    ]],
                    ['id' => 96, 'type' => 'accordion-item', 'data' => ['title' => 'Can I use Lambda CMS as a headless CMS?'], 'children' => [
                        $this->block(97, 'paragraph', ['content' => 'Yes. Lambda CMS includes a full REST API with authentication. You can use it as a headless CMS to power any frontend — React, Vue, Next.js, mobile apps, or anything else that can consume a JSON API.']),
                    ]],
                    ['id' => 98, 'type' => 'accordion-item', 'data' => ['title' => 'How does the template system work?'], 'children' => [
                        $this->block(99, 'paragraph', ['content' => 'Templates are reusable block layouts for different page types: blog index, single post, archive, search results, header, and footer. You can also create partial templates to use as reusable components inside other templates. Every template is fully editable in the block editor.']),
                    ]],
                    ['id' => 100, 'type' => 'accordion-item', 'data' => ['title' => 'Is Lambda CMS free?'], 'children' => [
                        $this->block(101, 'paragraph', ['content' => 'Lambda CMS is open source and free to use. You can self-host it on any server that runs PHP 8.4+ and a database. There are no license fees, no premium tiers, and no vendor lock-in.']),
                    ]],
                ]),
            ]),

            // ── 7. CTA ───────────────────────────────────────────────────
            $this->section(105, ['paddingY' => ['default' => 8], 'paddingX' => ['default' => 4], 'fullWidth' => false, 'innerMaxWidth' => '2xl', 'minHeight' => 'auto'], [
                $this->block(106, 'cta', [
                    'headline' => 'Ready to get started?',
                    'text' => 'Lambda CMS is open source, self-hosted, and built for developers who want full control over their content platform.',
                    'button_label' => 'View on GitHub',
                    'button_url' => 'https://github.com/mariusberget92/lambda-cms',
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
                    'mode' => 'flex',
                    'direction' => 'column',
                    'gap' => '0',
                    'padding' => 0,
                    'maxWidth' => 'full',
                ], [
                    $this->block(114, 'post-meta', ['showDate' => true, 'showAuthor' => true, 'showReadTime' => true]),
                    $this->block(115, 'post-taxonomy', ['showCategories' => true, 'showTags' => true]),
                    $this->block(117, 'post-body', []),
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
                    'source' => 'posts',
                    'filters' => [],
                    'sort' => ['field' => 'published_at', 'direction' => 'desc'],
                    'limit' => 8,
                    'columns' => 2,
                    'gap' => 'lg',
                ], [$this->templateBlock(210, $this->postCardTemplateId() ?? 0)]),
            ]),
        ];
    }

    private function searchBlocks(): array
    {
        return [
            $this->section(300, ['paddingY' => ['default' => 10], 'paddingX' => ['default' => 0], 'fullWidth' => true, 'minHeight' => 'auto'], [
                $this->block(311, 'heading', ['level' => 1, 'text' => 'Search']),
                // SearchBlock with its own card — heading hidden since H1 above provides the title
                $this->block(302, 'search', ['placeholder' => 'Search posts…', 'buttonLabel' => 'Search', 'scope' => 'posts', 'showHeading' => false]),
                $this->block(303, 'loop', [
                    'source' => 'posts',
                    'filters' => [['field' => 'title', 'op' => 'contains', 'urlParam' => 'q', 'value' => '']],
                    'sort' => ['field' => 'published_at', 'direction' => 'desc'],
                    'limit' => 10,
                    'columns' => 2,
                    'gap' => 'lg',
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
        return Template::where('title', 'Post Card')
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
     * @param  array  $children  nested blocks (container/section/loop only)
     * @param  array  $bindings  dynamic field bindings (e.g. ['text' => 'loop:title'])
     * @param  string  $customClasses  Tailwind classes applied to the BlockRenderer wrapper div
     * @param  string  $customCss  Inline CSS applied via <style>#block-{id} { … }</style>
     */
    private function block(int $id, string $type, array $data, array $children = [], array $bindings = [], string $customClasses = '', string $customCss = ''): array
    {
        $b = ['id' => $id, 'type' => $type, 'data' => $data];
        if (! empty($children)) {
            $b['children'] = $children;
        }
        if (! empty($bindings)) {
            $b['bindings'] = $bindings;
        }
        if ($customClasses !== '') {
            $b['customClasses'] = $customClasses;
        }
        if ($customCss !== '') {
            $b['customCss'] = $customCss;
        }

        return $b;
    }
}
