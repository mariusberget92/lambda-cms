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
            // ── 1. Hero — light background, large headline ──────────────
            $this->section(40, ['paddingY' => ['default' => 16], 'paddingX' => ['default' => 4], 'fullWidth' => false, 'innerMaxWidth' => '2xl', 'minHeight' => 'auto'], [
                $this->block(41, 'paragraph', ['content' => '<span style="display:inline-flex;align-items:center;gap:0.5rem;font-family:\'JetBrains Mono\',monospace;font-size:0.75rem;letter-spacing:0.05em;color:var(--soft);border:1px solid var(--line-strong);border-radius:9999px;padding:0.25rem 0.875rem;"><span style="display:inline-block;width:6px;height:6px;border-radius:50%;background:var(--accent);"></span> OPEN SOURCE &middot; MIT &middot; SELF-HOSTED</span>']),
                $this->block(42, 'spacer', ['height' => '1.5rem']),
                $this->block(43, 'paragraph', ['content' => '<span style="font-family:\'Space Grotesk\',sans-serif;font-weight:700;font-size:clamp(2.5rem,6vw,4.5rem);line-height:1.05;letter-spacing:-0.04em;color:var(--ink);display:block;">Build the whole site.<br><span style="color:var(--accent);">Drag, drop, schedule,</span><br>ship.</span>']),
                $this->block(44, 'spacer', ['height' => '1rem']),
                $this->block(45, 'paragraph', ['content' => '<span style="font-size:1.125rem;line-height:1.7;color:var(--soft);max-width:40rem;display:block;">Lambda CMS is a fast, self-hosted CMS <strong style="color:var(--ink);">+ CRM + email</strong> with a real drag-and-drop block editor and scheduled publishing baked in. No SaaS. No lock-in. You own the stack.</span>']),
                $this->block(46, 'spacer', ['height' => '1.5rem']),
                $this->block(47, 'container', [
                    'mode' => 'flex',
                    'direction' => 'row',
                    'wrap' => true,
                    'gap' => '0.75rem',
                    'padding' => 0,
                    'align' => 'center',
                    'maxWidth' => 'full',
                ], [
                    $this->block(48, 'button', [
                        'label' => 'Self-host in minutes',
                        'url' => 'https://github.com/mariusberget92/lambda-cms',
                        'variant' => 'filled',
                        'size' => 'lg',
                        'icon' => ['name' => 'lucide:arrow-right', 'position' => 'suffix'],
                    ]),
                    $this->block(49, 'button', [
                        'label' => 'Star on GitHub',
                        'url' => 'https://github.com/mariusberget92/lambda-cms',
                        'variant' => 'outline',
                        'size' => 'lg',
                        'icon' => ['name' => 'lucide:star', 'position' => 'prefix'],
                    ]),
                ]),
                $this->block(39, 'spacer', ['height' => '1rem']),
                $this->block(38, 'paragraph', ['content' => '<span style="font-family:\'JetBrains Mono\',monospace;font-size:0.8rem;color:var(--soft);letter-spacing:0.02em;">Laravel 12 &middot; Vue 3 &middot; Tailwind 4</span>']),
            ]),

            // ── 2. Block editor showcase ────────────────────────────────
            $this->section(50, ['paddingY' => ['default' => 10], 'paddingX' => ['default' => 4], 'fullWidth' => false, 'innerMaxWidth' => 'full', 'minHeight' => 'auto'], [
                $this->block(51, 'section-header', ['number' => '01', 'meta' => 'BLOCK EDITOR']),
                $this->block(52, 'spacer', ['height' => '1rem']),
                $this->block(53, 'heading', ['level' => 2, 'text' => 'Compose anything from 30+ blocks.']),
                $this->block(54, 'paragraph', ['content' => 'Six categories, drag-and-drop reordering, a layers tree, and a per-block style tab. Bind block content to live data with loops and post context. Click a block below to drop it on the canvas.']),
                $this->block(55, 'spacer', ['height' => '1.5rem']),
                $this->block(56, 'container', [
                    'mode' => 'flex',
                    'direction' => 'row',
                    'wrap' => true,
                    'gap' => '0.75rem',
                    'padding' => 0,
                    'maxWidth' => 'full',
                ], [
                    $this->block(501, 'button', ['label' => 'Heading', 'variant' => 'outline', 'size' => 'sm', 'icon' => ['name' => 'lucide:heading']]),
                    $this->block(502, 'button', ['label' => 'Paragraph', 'variant' => 'outline', 'size' => 'sm', 'icon' => ['name' => 'lucide:text']]),
                    $this->block(503, 'button', ['label' => 'Image', 'variant' => 'outline', 'size' => 'sm', 'icon' => ['name' => 'lucide:image']]),
                    $this->block(504, 'button', ['label' => 'Button', 'variant' => 'outline', 'size' => 'sm', 'icon' => ['name' => 'lucide:mouse-pointer-click']]),
                    $this->block(505, 'button', ['label' => 'Quote', 'variant' => 'outline', 'size' => 'sm', 'icon' => ['name' => 'lucide:quote']]),
                    $this->block(506, 'button', ['label' => 'Accordion', 'variant' => 'outline', 'size' => 'sm', 'icon' => ['name' => 'lucide:chevrons-down-up']]),
                    $this->block(507, 'button', ['label' => 'Tabs', 'variant' => 'outline', 'size' => 'sm', 'icon' => ['name' => 'lucide:panel-top']]),
                    $this->block(508, 'button', ['label' => 'Loop', 'variant' => 'outline', 'size' => 'sm', 'icon' => ['name' => 'lucide:repeat']]),
                    $this->block(509, 'button', ['label' => 'Stats', 'variant' => 'outline', 'size' => 'sm', 'icon' => ['name' => 'lucide:bar-chart-3']]),
                    $this->block(510, 'button', ['label' => 'Code', 'variant' => 'outline', 'size' => 'sm', 'icon' => ['name' => 'lucide:code']]),
                    $this->block(511, 'button', ['label' => 'Divider', 'variant' => 'outline', 'size' => 'sm', 'icon' => ['name' => 'lucide:minus']]),
                    $this->block(512, 'button', ['label' => 'List', 'variant' => 'outline', 'size' => 'sm', 'icon' => ['name' => 'lucide:list']]),
                ]),
            ]),

            // ── 3. Scheduling ───────────────────────────────────────────
            $this->section(60, ['paddingY' => ['default' => 10], 'paddingX' => ['default' => 4], 'fullWidth' => false, 'innerMaxWidth' => 'full', 'minHeight' => 'auto'], [
                $this->block(61, 'section-header', ['number' => '02', 'meta' => 'SCHEDULING']),
                $this->block(62, 'spacer', ['height' => '1rem']),
                $this->block(63, 'heading', ['level' => 2, 'text' => 'Write today. Publish exactly when you mean to.']),
                $this->block(64, 'paragraph', ['content' => 'Queue posts and email campaigns for a future date and the scheduler dispatches them automatically — down to the minute. Autosave keeps drafts safe, revisions let you roll back up to 20 versions, and a one-click editorial approval ensures the entire team stays on the same page.']),
                $this->block(65, 'spacer', ['height' => '1.5rem']),
                $this->block(66, 'container', [
                    'mode' => 'flex',
                    'direction' => 'row',
                    'wrap' => true,
                    'gap' => '1rem',
                    'padding' => 0,
                    'maxWidth' => 'full',
                ], [
                    $this->block(67, 'stat-card', ['value' => '128', 'label' => 'Published', 'trend' => 'Posts shipped on schedule', 'trendTone' => 'positive'], [], [], '', 'flex:1;min-width:200px'),
                    $this->block(68, 'stat-card', ['value' => '20', 'label' => 'Revisions', 'trend' => 'Roll back any time', 'trendTone' => 'neutral'], [], [], '', 'flex:1;min-width:200px'),
                    $this->block(69, 'stat-card', ['value' => '∞', 'label' => 'Drafts', 'trend' => 'Autosave keeps your work safe', 'trendTone' => 'neutral'], [], [], '', 'flex:1;min-width:200px'),
                ]),
            ]),

            // ── 4. CRM & Email ──────────────────────────────────────────
            $this->section(70, ['paddingY' => ['default' => 10], 'paddingX' => ['default' => 4], 'fullWidth' => false, 'innerMaxWidth' => 'full', 'minHeight' => 'auto'], [
                $this->block(71, 'section-header', ['number' => '03', 'meta' => 'CRM & EMAIL']),
                $this->block(72, 'spacer', ['height' => '1rem']),
                $this->block(73, 'heading', ['level' => 2, 'text' => 'Content, customers, and campaigns — one app.']),
                $this->block(74, 'paragraph', ['content' => 'A full CRM with contacts, companies, deals, and call lists — and fully editable, subscriber-managed, and scheduled email campaigns. Click a card to see how it works.']),
                $this->block(75, 'spacer', ['height' => '1.5rem']),
                $this->block(76, 'container', [
                    'mode' => 'flex',
                    'direction' => 'row',
                    'wrap' => true,
                    'gap' => '1.5rem',
                    'padding' => 0,
                    'maxWidth' => 'full',
                ], [
                    $this->block(77, 'container', [
                        'mode' => 'flex',
                        'direction' => 'column',
                        'gap' => '0.75rem',
                        'padding' => 0,
                        'maxWidth' => 'full',
                    ], [
                        $this->block(78, 'heading', ['level' => 3, 'text' => 'Email campaigns']),
                        $this->block(79, 'icon-list', [
                            'direction' => 'vertical',
                            'gap' => '0.5rem',
                            'iconSize' => '1.1em',
                            'iconColor' => 'var(--accent)',
                            'items' => [
                                ['icon' => 'lucide:mail', 'text' => 'Compose in TipTap editor'],
                                ['icon' => 'lucide:send', 'text' => 'Schedule or send instantly'],
                                ['icon' => 'lucide:bar-chart-3', 'text' => 'Delivery tracking with reports'],
                            ],
                        ]),
                    ], [], 'sidebar-card', 'flex:1;min-width:260px'),

                    $this->block(80, 'container', [
                        'mode' => 'flex',
                        'direction' => 'column',
                        'gap' => '0.75rem',
                        'padding' => 0,
                        'maxWidth' => 'full',
                    ], [
                        $this->block(81, 'heading', ['level' => 3, 'text' => 'Editable system mail']),
                        $this->block(82, 'icon-list', [
                            'direction' => 'vertical',
                            'gap' => '0.5rem',
                            'iconSize' => '1.1em',
                            'iconColor' => 'var(--accent)',
                            'items' => [
                                ['icon' => 'lucide:file-edit', 'text' => 'Five customizable templates'],
                                ['icon' => 'lucide:braces', 'text' => 'Merge-tag placeholders'],
                                ['icon' => 'lucide:rotate-ccw', 'text' => 'Reset to default any time'],
                            ],
                        ]),
                    ], [], 'sidebar-card', 'flex:1;min-width:260px'),

                    $this->block(83, 'container', [
                        'mode' => 'flex',
                        'direction' => 'column',
                        'gap' => '0.75rem',
                        'padding' => 0,
                        'maxWidth' => 'full',
                    ], [
                        $this->block(84, 'heading', ['level' => 3, 'text' => 'CSV in, CSV out']),
                        $this->block(85, 'icon-list', [
                            'direction' => 'vertical',
                            'gap' => '0.5rem',
                            'iconSize' => '1.1em',
                            'iconColor' => 'var(--accent)',
                            'items' => [
                                ['icon' => 'lucide:upload', 'text' => 'Bulk-import contacts or subscribers'],
                                ['icon' => 'lucide:download', 'text' => 'One-click CSV export'],
                                ['icon' => 'lucide:shield-check', 'text' => 'No vendor lock-in on your data'],
                            ],
                        ]),
                    ], [], 'sidebar-card', 'flex:1;min-width:260px'),
                ]),
            ]),

            // ── 5. Batteries fully included — feature grid ──────────────
            $this->section(90, ['paddingY' => ['default' => 10], 'paddingX' => ['default' => 4], 'fullWidth' => false, 'innerMaxWidth' => 'full', 'minHeight' => 'auto'], [
                $this->block(91, 'section-header', ['number' => '04', 'meta' => 'EVERYTHING ELSE']),
                $this->block(92, 'spacer', ['height' => '1rem']),
                $this->block(93, 'heading', ['level' => 2, 'text' => 'Batteries fully included.']),
                $this->block(94, 'spacer', ['height' => '1.5rem']),
                $this->block(95, 'container', [
                    'mode' => 'flex',
                    'direction' => 'row',
                    'wrap' => true,
                    'gap' => '1rem',
                    'padding' => 0,
                    'maxWidth' => 'full',
                ], [
                    $this->block(601, 'stat-card', ['value' => 'Image editor', 'label' => 'MEDIA', 'trend' => 'Crop, resize, flip & filter — right in the media library', 'trendTone' => 'neutral'], [], [], '', 'flex:1;min-width:240px'),
                    $this->block(602, 'stat-card', ['value' => 'Template system', 'label' => 'STRUCTURE', 'trend' => '7 reusable template types that control the entire site', 'trendTone' => 'neutral'], [], [], '', 'flex:1;min-width:240px'),
                    $this->block(603, 'stat-card', ['value' => 'Comments', 'label' => 'COMMUNITY', 'trend' => 'Threaded comments with moderation, reply-by-email', 'trendTone' => 'neutral'], [], [], '', 'flex:1;min-width:240px'),
                    $this->block(604, 'stat-card', ['value' => 'ZIP & recovery', 'label' => 'BACKUPS', 'trend' => 'Export the entire site as a downloadable ZIP archive', 'trendTone' => 'neutral'], [], [], '', 'flex:1;min-width:240px'),
                    $this->block(605, 'stat-card', ['value' => 'Signed webhooks', 'label' => 'INTEGRATION', 'trend' => 'HMAC-signed outbound webhooks on any event', 'trendTone' => 'neutral'], [], [], '', 'flex:1;min-width:240px'),
                    $this->block(606, 'stat-card', ['value' => 'Bitwise roles', 'label' => 'SECURITY', 'trend' => '42 granular permissions with role-based access control', 'trendTone' => 'neutral'], [], [], '', 'flex:1;min-width:240px'),
                    $this->block(607, 'stat-card', ['value' => 'Users & roles', 'label' => 'ADMIN', 'trend' => 'Invite users, assign roles, set per-permission access', 'trendTone' => 'neutral'], [], [], '', 'flex:1;min-width:240px'),
                    $this->block(608, 'stat-card', ['value' => 'Import / export', 'label' => 'DATA', 'trend' => 'Bulk CSV import and export for contacts and subscribers', 'trendTone' => 'neutral'], [], [], '', 'flex:1;min-width:240px'),
                    $this->block(609, 'stat-card', ['value' => 'Full text search', 'label' => 'SEARCH', 'trend' => 'Instant full-text search across posts and pages', 'trendTone' => 'neutral'], [], [], '', 'flex:1;min-width:240px'),
                    $this->block(610, 'stat-card', ['value' => 'Design tokens', 'label' => 'THEMING', 'trend' => 'CSS custom properties power the entire frontend look', 'trendTone' => 'neutral'], [], [], '', 'flex:1;min-width:240px'),
                    $this->block(611, 'stat-card', ['value' => 'REST API', 'label' => 'HEADLESS', 'trend' => 'Full API with token auth for headless frontends', 'trendTone' => 'neutral'], [], [], '', 'flex:1;min-width:240px'),
                    $this->block(612, 'stat-card', ['value' => 'SEO & feeds', 'label' => 'GROWTH', 'trend' => 'Per-page SEO meta, RSS feed, and XML sitemap', 'trendTone' => 'neutral'], [], [], '', 'flex:1;min-width:240px'),
                ]),
            ]),

            // ── 6. For developers — code example ────────────────────────
            $this->section(100, ['paddingY' => ['default' => 10], 'paddingX' => ['default' => 4], 'fullWidth' => false, 'innerMaxWidth' => 'full', 'minHeight' => 'auto'], [
                $this->block(101, 'section-header', ['number' => '05', 'meta' => 'FOR DEVELOPERS']),
                $this->block(102, 'spacer', ['height' => '1rem']),
                $this->block(103, 'heading', ['level' => 2, 'text' => 'Headless when you want it. Yours, always.']),
                $this->block(104, 'paragraph', ['content' => 'A clean REST API, git-friendly, HMAC-signed webhooks, and a design-token engine that themes admin and frontend from one file. Self-host on any server that runs PHP 8.4+.']),
                $this->block(105, 'spacer', ['height' => '1.5rem']),
                $this->block(106, 'code', [
                    'language' => 'bash',
                    'code' => "$ git clone lambda-cms.git && cd lambda-cms\n$ composer install && npm install\n$ cp .env.example .env\n$ php artisan key:generate\n$ php artisan serve\n\n# Visit http://localhost:8000/install",
                ]),
                $this->block(107, 'spacer', ['height' => '1.5rem']),
                $this->block(108, 'container', [
                    'mode' => 'flex',
                    'direction' => 'row',
                    'wrap' => true,
                    'gap' => '1rem',
                    'padding' => 0,
                    'maxWidth' => 'full',
                ], [
                    $this->block(109, 'stat-card', ['value' => 'Laravel 12', 'label' => 'Framework'], [], [], '', 'flex:1;min-width:140px'),
                    $this->block(110, 'stat-card', ['value' => 'PHP 8.4', 'label' => 'Runtime'], [], [], '', 'flex:1;min-width:140px'),
                    $this->block(111, 'stat-card', ['value' => 'Inertia 2', 'label' => 'Bridge'], [], [], '', 'flex:1;min-width:140px'),
                    $this->block(112, 'stat-card', ['value' => 'Vue 3', 'label' => 'Frontend'], [], [], '', 'flex:1;min-width:140px'),
                    $this->block(113, 'stat-card', ['value' => 'Tailwind 4', 'label' => 'CSS'], [], [], '', 'flex:1;min-width:140px'),
                ]),
            ]),

            // ── 7. CTA ──────────────────────────────────────────────────
            $this->section(120, ['paddingY' => ['default' => 12], 'paddingX' => ['default' => 4], 'fullWidth' => false, 'innerMaxWidth' => '2xl', 'minHeight' => 'auto'], [
                $this->block(121, 'heading', ['level' => 2, 'text' => 'Own your CMS. Start in minutes.']),
                $this->block(122, 'paragraph', ['content' => 'Free, open source, MIT. No vendors, no cloud, no cards.']),
                $this->block(123, 'spacer', ['height' => '1rem']),
                $this->block(124, 'container', [
                    'mode' => 'flex',
                    'direction' => 'row',
                    'wrap' => true,
                    'gap' => '0.75rem',
                    'padding' => 0,
                    'align' => 'center',
                    'maxWidth' => 'full',
                ], [
                    $this->block(125, 'button', [
                        'label' => 'Self-host in minutes',
                        'url' => 'https://github.com/mariusberget92/lambda-cms',
                        'variant' => 'filled',
                        'size' => 'lg',
                        'icon' => ['name' => 'lucide:arrow-right', 'position' => 'suffix'],
                    ]),
                    $this->block(126, 'button', [
                        'label' => 'Star on GitHub',
                        'url' => 'https://github.com/mariusberget92/lambda-cms',
                        'variant' => 'outline',
                        'size' => 'lg',
                        'icon' => ['name' => 'lucide:star', 'position' => 'prefix'],
                    ]),
                ]),
            ]),

            // ── 8. Latest posts + sidebar ───────────────────────────────
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
                            'limit' => 6,
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
