<?php

namespace Database\Seeders;

use App\Models\Template;
use Illuminate\Database\Seeder;

class CrateSeeder extends Seeder
{
    public function run(): void
    {
        Template::where('type', 'header')->where('title', 'Default Header')
            ->update(['blocks' => $this->headerBlocks()]);
        Template::where('type', 'footer')->where('title', 'Default Footer')
            ->update(['blocks' => $this->footerBlocks()]);
        Template::where('type', 'blog-index')->where('title', 'Default Blog Index')
            ->update(['blocks' => $this->landingPageBlocks()]);
        $this->command->info('Crate design applied.');
    }

    // ── Deterministic PRNG (matches the Claude Design JS) ────────────────────

    private function lpRand(float $s): float
    {
        $x = sin($s * 99.13 + 7.7) * 43758.5453;

        return $x - floor($x);
    }

    private function wave(int $n, int $seed, string $color, string $accentColor = '', int $accentEvery = 0): string
    {
        $out = '';
        for ($i = 0; $i < $n; $i++) {
            $a = $this->lpRand($i + $seed) * 0.6 + $this->lpRand($i * 1.7 + $seed) * 0.4;
            $h = round(($a * 0.88 + 0.12) * 100, 1);
            $isAccent = $accentEvery > 0 && ($i % $accentEvery === (int) floor($accentEvery / 2));
            $c = ($isAccent && $accentColor !== '') ? $accentColor : $color;
            $out .= sprintf('<div style="flex:1 1 0;min-width:0;height:%s%%;background:%s;"></div>', $h, $c);
        }

        return $out;
    }

    // ── SVG logo data-URI ─────────────────────────────────────────────────────

    private function logoUri(int $size = 28): string
    {
        $svg = '<svg width="'.$size.'" height="'.$size.'" viewBox="0 0 120 120" fill="none" '
             .'xmlns="http://www.w3.org/2000/svg">'
             .'<path d="M88 31 H40 V89 H88" stroke="#e9e4dc" stroke-width="13"/>'
             .'<rect x="72" y="52" width="16" height="16" fill="#e0913f"/>'
             .'</svg>';

        return 'data:image/svg+xml,'.rawurlencode($svg);
    }

    // ── Block / section helpers ───────────────────────────────────────────────

    private function section(int $id, array $data, array $children, string $customId = ''): array
    {
        $s = ['id' => $id, 'type' => 'section', 'data' => $data, 'children' => $children];
        if ($customId !== '') {
            $s['customId'] = $customId;
        }

        return $s;
    }

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

    private function html(int $id, string $content): array
    {
        return $this->block($id, 'html', ['content' => $content]);
    }

    private function nopad(): array
    {
        return ['fullWidth' => true, 'paddingY' => ['default' => 0], 'paddingX' => ['default' => 0], 'minHeight' => 'auto'];
    }

    // ── Templates ─────────────────────────────────────────────────────────────

    private function headerBlocks(): array
    {
        return [
            $this->block(400, 'nav-header', [
                'logoText' => 'CRATE',
                'showSearch' => false,
                'sticky' => true,
                'links' => [
                    ['label' => 'Features',     'url' => '#features'],
                    ['label' => 'How It Works', 'url' => '#how'],
                    ['label' => 'Pricing',      'url' => '#pricing'],
                    ['label' => 'BUY — $25',    'url' => '#pricing'],
                ],
            ]),
        ];
    }

    private function footerBlocks(): array
    {
        return [
            $this->block(500, 'site-footer', [
                'tagline' => 'A sample organizer for people with too many samples.',
                'copyright' => '© '.date('Y').' CRATE',
                'showRss' => false,
                'columns' => [
                    ['heading' => 'Product', 'links' => [
                        ['label' => 'Features',     'url' => '#features'],
                        ['label' => 'How It Works', 'url' => '#how'],
                        ['label' => 'Pricing',      'url' => '#pricing'],
                    ]],
                    ['heading' => 'Support', 'links' => [
                        ['label' => 'Changelog', 'url' => '/changelog'],
                        ['label' => 'Support',   'url' => '/support'],
                    ]],
                ],
            ]),
        ];
    }

    private function landingPageBlocks(): array
    {
        return [
            $this->section(10, $this->nopad(), [$this->html(11, $this->globalCss())]),
            $this->section(100, $this->nopad(), [$this->html(101, $this->heroHtml())]),
            $this->section(200, $this->nopad(), [$this->html(201, $this->featuresHtml())], 'features'),
            $this->section(300, $this->nopad(), [$this->html(301, $this->showcaseHtml())], 'how'),
            $this->section(600, $this->nopad(), [$this->html(601, $this->pricingHtml())], 'pricing'),
        ];
    }

    // ── HTML sections ─────────────────────────────────────────────────────────

    private function globalCss(): string
    {
        $nav = $this->logoUri(28);
        $foot = $this->logoUri(22);

        return <<<CSS
<style>
/* ── Crate dark theme: override Lambda CMS CSS variables ── */
:root {
    --panel: #0c0b0a;
    --bg: #0c0b0a;
    --ink: #e9e4dc;
    --soft: #948b7f;
    --line-strong: #2a2622;
    --line: #1e1b18;
    --accent: #e0913f;
    --blog-radius: 0px;
}
html { scroll-behavior: smooth; scroll-padding-top: 68px; }
body { background: #0c0b0a !important; }

/* ── Nav: Terminal C logo + link styling ── */
.nav-brand__mark {
    border-color: #2a2622 !important;
    border-radius: 0 !important;
    background-image: url("{$nav}") !important;
    background-size: 20px !important;
    background-repeat: no-repeat !important;
    background-position: center !important;
}
.nav-brand__mark span { display: none !important; }
.nav-brand__name {
    font-family: "JetBrains Mono", ui-monospace, monospace !important;
    letter-spacing: 0.3em !important;
    font-size: 13px !important;
}
.nav-link {
    font-family: "JetBrains Mono", ui-monospace, monospace !important;
    font-size: 11px !important;
    letter-spacing: 0.14em !important;
}
.nav-link:last-child {
    color: #e0913f !important;
    border: 1px solid rgba(224,145,63,0.5) !important;
    padding: 8px 14px !important;
}
.nav-link:last-child:hover { background: rgba(224,145,63,0.13) !important; }

/* ── Footer: Terminal C logo ── */
.footer-brand__mark {
    border-color: #2a2622 !important;
    border-radius: 0 !important;
    background-image: url("{$foot}") !important;
    background-size: 16px !important;
    background-repeat: no-repeat !important;
    background-position: center !important;
}
.footer-brand__mark span { display: none !important; }
.site-footer { margin-top: 0 !important; }
</style>
CSS;
    }

    private function heroHtml(): string
    {
        $wave = $this->wave(64, 9, '#6b6358', '#e0913f', 9);

        return <<<HTML
<style>
.c-hero {
    background: #0c0b0a; border-bottom: 1px solid #2a2622;
    padding: 80px 56px;
    display: grid; grid-template-columns: 1fr 488px; gap: 56px; align-items: center;
    font-family: -apple-system, BlinkMacSystemFont, "Helvetica Neue", sans-serif;
    -webkit-font-smoothing: antialiased; color: #e9e4dc;
}
.c-eyebrow { font-family: "JetBrains Mono", monospace; font-size: 11px; letter-spacing: 0.26em; color: #635b51; margin-bottom: 26px; }
.c-h1 { font-size: clamp(46px, 5.5vw, 76px); font-weight: 700; line-height: 0.98; letter-spacing: -0.03em; margin: 0 0 26px; }
.c-h1 em { color: #e0913f; font-style: normal; }
.c-sub { font-size: 18px; line-height: 1.6; color: #948b7f; max-width: 440px; margin: 0 0 32px; }
.c-cta-row { display: flex; align-items: center; gap: 14px; margin-bottom: 18px; flex-wrap: wrap; }
.c-btn { display: inline-flex; align-items: center; font-family: "JetBrains Mono", monospace; font-size: 13px; letter-spacing: 0.08em; font-weight: 600; padding: 14px 24px; border: 1px solid; text-decoration: none; transition: all 0.14s; }
.c-btn.primary { background: #e0913f; color: #15110b; border-color: #e0913f; }
.c-btn.primary:hover { filter: brightness(1.08); }
.c-btn.ghost { color: #948b7f; border-color: #2a2622; }
.c-btn.ghost:hover { border-color: #635b51; color: #e9e4dc; }
.c-note { font-family: "JetBrains Mono", monospace; font-size: 11px; letter-spacing: 0.08em; color: #635b51; margin: 0; }
.c-termwin { background: #141210; border: 1px solid #2a2622; box-shadow: 0 30px 80px rgba(0,0,0,0.5); }
.c-tw-bar { height: 38px; display: flex; align-items: center; gap: 7px; padding: 0 14px; border-bottom: 1px solid #2a2622; background: #100e0c; }
.c-tw-dot { width: 9px; height: 9px; border-radius: 50%; background: #232019; border: 1px solid #2a2622; display: inline-block; flex-shrink: 0; }
.c-tw-title { font-family: "JetBrains Mono", monospace; font-size: 11px; color: #635b51; letter-spacing: 0.1em; margin-left: 10px; }
.c-tw-body { padding: 22px 22px 24px; font-family: "JetBrains Mono", monospace; font-size: 13.5px; line-height: 2.05; color: #948b7f; }
.c-tl { white-space: nowrap; }
.c-tl.out { color: #635b51; }
.c-prompt { color: #e0913f; margin-right: 8px; }
.c-ok { color: #e0913f; font-size: 10px; border: 1px solid rgba(224,145,63,0.5); padding: 0 5px; margin-left: 6px; letter-spacing: 0.1em; }
.c-cursor { display: inline-block; width: 9px; height: 17px; background: #e0913f; margin-left: 7px; vertical-align: -3px; animation: cBlink 1.05s steps(1) infinite; }
@keyframes cBlink { 0%,50% { opacity:1; } 50.01%,100% { opacity:0; } }
.c-tw-wave { padding: 16px 0 14px; display: flex; align-items: center; gap: 3px; height: 88px; }
@media (max-width: 900px) { .c-hero { grid-template-columns: 1fr; padding: 48px 24px; } }
</style>
<section class="c-hero">
    <div>
        <div class="c-eyebrow">SAMPLE&nbsp;ORGANIZER&nbsp;·&nbsp;WINDOWS</div>
        <h1 class="c-h1">Stop digging.<br><em>Start finding.</em></h1>
        <p class="c-sub">Crate scans a folder of samples, reads the BPM and key of every file, and turns the pile into a library you can actually browse — waveforms, filters and all.</p>
        <div class="c-cta-row">
            <a href="#pricing" class="c-btn primary">BUY HERE — $25</a>
            <a href="#how" class="c-btn ghost">See how it works ↓</a>
        </div>
        <p class="c-note">One-time purchase · lifetime license · no subscription</p>
    </div>
    <div class="c-termwin">
        <div class="c-tw-bar">
            <span class="c-tw-dot"></span><span class="c-tw-dot"></span><span class="c-tw-dot"></span>
            <span class="c-tw-title">crate — scan</span>
        </div>
        <div class="c-tw-body">
            <div class="c-tl"><span class="c-prompt">$</span>crate scan ~/Samples</div>
            <div class="c-tl out">▸ 4,812 files indexed</div>
            <div class="c-tl out">▸ bpm + key detected <span class="c-ok">ok</span></div>
            <div class="c-tl out">▸ waveforms cached</div>
            <div class="c-tw-wave">{$wave}</div>
            <div class="c-tl">ready<span class="c-cursor"></span></div>
        </div>
    </div>
</section>
HTML;
    }

    private function featuresHtml(): string
    {
        return <<<'HTML'
<style>
.c-features {
    background: #0c0b0a; border-bottom: 1px solid #2a2622; padding: 72px 56px;
    font-family: -apple-system, BlinkMacSystemFont, "Helvetica Neue", sans-serif;
    -webkit-font-smoothing: antialiased;
}
.c-kicker { font-family: "JetBrains Mono", monospace; font-size: 11px; letter-spacing: 0.24em; color: #e0913f; display: block; margin-bottom: 16px; }
.c-h2 { font-size: 40px; font-weight: 700; letter-spacing: -0.025em; color: #e9e4dc; margin: 0 0 48px; }
.c-feat-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 1px; background: #2a2622; border: 1px solid #2a2622; margin-bottom: 24px; }
.c-feat { background: #0c0b0a; padding: 32px 28px; }
.c-fn { font-family: "JetBrains Mono", monospace; font-size: 11px; color: #e0913f; letter-spacing: 0.1em; margin-bottom: 12px; }
.c-ft { font-size: 18px; font-weight: 700; color: #e9e4dc; letter-spacing: -0.02em; margin-bottom: 10px; }
.c-fd { font-size: 14px; line-height: 1.65; color: #948b7f; }
.c-fnote { font-family: "JetBrains Mono", monospace; font-size: 12px; color: #635b51; margin: 0; }
.c-fnote .acc { color: #e0913f; }
@media (max-width: 768px) { .c-feat-grid { grid-template-columns: 1fr; } .c-features { padding: 48px 24px; } }
</style>
<section class="c-features">
    <span class="c-kicker">WHAT IT DOES</span>
    <h2 class="c-h2">A librarian for your sample folder.</h2>
    <div class="c-feat-grid">
        <div class="c-feat">
            <div class="c-fn">01</div>
            <div class="c-ft">Reads BPM &amp; key</div>
            <div class="c-fd">Point Crate at a folder and it analyzes every file — tempo and musical key tagged automatically, no manual labelling.</div>
        </div>
        <div class="c-feat">
            <div class="c-fn">02</div>
            <div class="c-ft">Waveform on every row</div>
            <div class="c-fd">See the shape of a sample before you hear it. Spot the loop, the one-shot and the dead air at a glance.</div>
        </div>
        <div class="c-feat">
            <div class="c-fn">03</div>
            <div class="c-ft">Filter down to the one</div>
            <div class="c-fd">Narrow by BPM range, key, type and tags until the right sound is the only one left in the list.</div>
        </div>
    </div>
    <p class="c-fnote"><span class="acc">▸</span> Nothing is moved or copied — your files stay exactly where they are.</p>
</section>
HTML;
    }

    private function showcaseHtml(): string
    {
        $rows = [
            //  name                    seed  bpm   key      type    playing  sel
            ['dusty_rhodes_loop_01',    4,   '88', 'F min', 'LOOP', false,   true],
            ['tape_kick_punch',         12,  '—',  '—',     'ONE',  true,    false],
            ['vinyl_crackle_bed',       31,  '—',  '—',     'FX',   false,   false],
            ['rim_shuffle_92',          7,   '92', 'A min', 'PERC', false,   false],
            ['sub_bass_Fmin',           19,  '88', 'F min', 'BASS', false,   false],
        ];

        $rowsHtml = '';
        foreach ($rows as [$name, $seed, $bpm, $key, $type, $playing, $sel]) {
            $waveColor = $playing ? '#e0913f' : '#6b6358';
            $waveHtml = $this->wave(48, (int) $seed, $waveColor);
            $cls = 'c-row'.($sel ? ' sel' : '').($playing ? ' playing' : '');
            $icon = $playing
                ? '<span class="c-dot"></span>'
                : '<span class="c-star">·</span>';
            $nameColor = ($sel || $playing) ? '#e9e4dc' : '#948b7f';
            $rowsHtml .= <<<ROW

        <div class="{$cls}">
            <span class="c-rn">{$icon}<span style="font-family:'JetBrains Mono',monospace;font-size:12px;color:{$nameColor};">{$name}.wav</span></span>
            <span class="c-rw">{$waveHtml}</span>
            <span class="c-rm">{$bpm}</span><span class="c-rm">{$key}</span><span class="c-rt">{$type}</span>
        </div>
ROW;
        }

        return <<<HTML
<style>
.c-showcase {
    background: #0c0b0a; border-bottom: 1px solid #2a2622; padding: 72px 56px;
    font-family: -apple-system, BlinkMacSystemFont, "Helvetica Neue", sans-serif;
    -webkit-font-smoothing: antialiased;
}
.c-sc-head { text-align: center; margin-bottom: 48px; }
.c-win { background: #141210; border: 1px solid #2a2622; }
.c-win-bar { height: 38px; display: flex; align-items: center; gap: 7px; padding: 0 14px; border-bottom: 1px solid #2a2622; background: #100e0c; }
.c-win-crumb { font-family: "JetBrains Mono", monospace; font-size: 11px; color: #635b51; margin-left: 10px; flex: 1; }
.c-win-count { font-family: "JetBrains Mono", monospace; font-size: 11px; color: #635b51; }
.c-list-head { display: grid; grid-template-columns: 2fr 3fr 60px 80px 60px; padding: 8px 16px; font-family: "JetBrains Mono", monospace; font-size: 10px; letter-spacing: 0.1em; color: #635b51; border-bottom: 1px solid #2a2622; }
.c-row { display: grid; grid-template-columns: 2fr 3fr 60px 80px 60px; padding: 10px 16px; border-bottom: 1px solid #1e1b18; align-items: center; }
.c-row.sel { background: #1b1816; }
.c-row.playing { background: rgba(224,145,63,0.07); }
.c-rn { display: flex; align-items: center; gap: 8px; overflow: hidden; min-width: 0; }
.c-dot { width: 6px; height: 6px; border-radius: 50%; background: #e0913f; display: inline-block; flex-shrink: 0; }
.c-star { color: #635b51; flex-shrink: 0; }
.c-rw { display: flex; align-items: center; gap: 2px; height: 20px; overflow: hidden; }
.c-rw > div { flex: 1 1 0; min-width: 0; }
.c-rm { font-family: "JetBrains Mono", monospace; font-size: 11px; color: #948b7f; text-align: right; }
.c-rt { font-family: "JetBrains Mono", monospace; font-size: 10px; color: #635b51; text-align: right; letter-spacing: 0.05em; }
@media (max-width: 768px) {
    .c-showcase { padding: 48px 24px; }
    .c-list-head, .c-row { grid-template-columns: 1fr 2fr 50px; }
    .c-list-head span:nth-child(n+4), .c-row span:nth-child(n+4) { display: none; }
}
</style>
<section class="c-showcase">
    <div class="c-sc-head">
        <span class="c-kicker" style="font-family:'JetBrains Mono',monospace;font-size:11px;letter-spacing:0.24em;color:#e0913f;display:block;margin-bottom:16px;">THE LIBRARY</span>
        <h2 class="c-h2" style="font-size:40px;font-weight:700;letter-spacing:-0.025em;color:#e9e4dc;margin:0;">Browse by ear and by number.</h2>
    </div>
    <div class="c-win">
        <div class="c-win-bar">
            <span style="width:9px;height:9px;border-radius:50%;background:#232019;border:1px solid #2a2622;display:inline-block;flex-shrink:0;"></span>
            <span style="width:9px;height:9px;border-radius:50%;background:#232019;border:1px solid #2a2622;display:inline-block;flex-shrink:0;"></span>
            <span style="width:9px;height:9px;border-radius:50%;background:#232019;border:1px solid #2a2622;display:inline-block;flex-shrink:0;"></span>
            <span class="c-win-crumb">Library / Dusty Soul Vol.2 / Loops</span>
            <span class="c-win-count">812</span>
        </div>
        <div class="c-list-head">
            <span>NAME</span><span>WAVEFORM</span>
            <span style="text-align:right;">BPM</span><span style="text-align:right;">KEY</span><span style="text-align:right;">TYPE</span>
        </div>{$rowsHtml}
    </div>
</section>
HTML;
    }

    private function pricingHtml(): string
    {
        return <<<'HTML'
<style>
.c-pricing {
    background: #0c0b0a; padding: 72px 56px;
    font-family: -apple-system, BlinkMacSystemFont, "Helvetica Neue", sans-serif;
    -webkit-font-smoothing: antialiased;
}
.c-pcard { display: grid; grid-template-columns: 1fr 1fr; border: 1px solid #2a2622; background: #141210; max-width: 900px; margin: 0 auto; }
.c-pl { padding: 48px; border-right: 1px solid #2a2622; }
.c-pr { padding: 48px; }
.c-pk { font-family: "JetBrains Mono", monospace; font-size: 11px; letter-spacing: 0.24em; color: #e0913f; display: block; margin-bottom: 24px; }
.c-pamt { display: flex; align-items: flex-start; gap: 4px; margin-bottom: 20px; line-height: 1; }
.c-pcur { font-size: 36px; font-weight: 700; color: #e9e4dc; margin-top: 14px; }
.c-pval { font-size: 72px; font-weight: 700; color: #e9e4dc; letter-spacing: -0.04em; }
.c-ponce { font-family: "JetBrains Mono", monospace; font-size: 12px; color: #635b51; letter-spacing: 0.1em; align-self: flex-end; margin-bottom: 10px; margin-left: 8px; }
.c-pblurb { font-size: 15px; line-height: 1.65; color: #948b7f; margin: 0 0 32px; max-width: 320px; }
.c-pbtn { display: inline-flex; align-items: center; font-family: "JetBrains Mono", monospace; font-size: 15px; letter-spacing: 0.14em; font-weight: 600; padding: 17px 40px; background: #e0913f; color: #15110b; border: 1px solid #e0913f; text-decoration: none; transition: filter 0.14s; }
.c-pbtn:hover { filter: brightness(1.08); }
.c-pnote { font-family: "JetBrains Mono", monospace; font-size: 11px; letter-spacing: 0.08em; color: #635b51; margin: 12px 0 0; }
.c-incl-head { font-family: "JetBrains Mono", monospace; font-size: 11px; letter-spacing: 0.24em; color: #635b51; margin-bottom: 20px; }
.c-incl { list-style: none; padding: 0; margin: 0 0 32px; display: flex; flex-direction: column; gap: 14px; }
.c-incl li { font-size: 14px; color: #948b7f; display: flex; gap: 10px; line-height: 1.5; }
.c-incl .acc { color: #e0913f; font-family: "JetBrains Mono", monospace; flex-shrink: 0; }
.c-req { font-family: "JetBrains Mono", monospace; font-size: 10px; letter-spacing: 0.12em; color: #635b51; margin: 0; }
@media (max-width: 768px) {
    .c-pcard { grid-template-columns: 1fr; }
    .c-pl { border-right: none; border-bottom: 1px solid #2a2622; padding: 32px 24px; }
    .c-pr { padding: 32px 24px; }
    .c-pricing { padding: 48px 24px; }
}
</style>
<section class="c-pricing">
    <div class="c-pcard">
        <div class="c-pl">
            <span class="c-pk">CRATE FOR WINDOWS</span>
            <div class="c-pamt">
                <span class="c-pcur">$</span><span class="c-pval">25</span>
                <span class="c-ponce">one-time</span>
            </div>
            <p class="c-pblurb">Buy once, keep it forever. No login, no monthly fee — just a faster way to find the sound in your head.</p>
            <a href="#" class="c-pbtn">BUY HERE</a>
            <p class="c-pnote">Secure checkout · instant download</p>
        </div>
        <div class="c-pr">
            <div class="c-incl-head">WHAT'S INCLUDED</div>
            <ul class="c-incl">
                <li><span class="acc">▸</span> Lifetime license — one machine</li>
                <li><span class="acc">▸</span> Free updates within v1.x</li>
                <li><span class="acc">▸</span> No account, no subscription</li>
                <li><span class="acc">▸</span> WAV · AIFF · FLAC · MP3 · OGG</li>
            </ul>
            <p class="c-req">REQUIRES WINDOWS 10 / 11 · 64-BIT</p>
        </div>
    </div>
</section>
HTML;
    }
}
