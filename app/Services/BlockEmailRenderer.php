<?php

namespace App\Services;

/**
 * Renders block editor JSON as email-safe HTML with inline styles.
 * Only common content blocks are supported; unknown blocks are skipped.
 */
class BlockEmailRenderer
{
    public function render(array $blocks, string $unsubscribeUrl = ''): string
    {
        $html = '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head>'
              . '<body style="margin:0;padding:0;background:#f4f4f5;font-family:sans-serif;">'
              . '<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f5;padding:32px 16px;">'
              . '<tr><td align="center">'
              . '<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:8px;overflow:hidden;">'
              . '<tr><td style="padding:32px 40px;">';

        foreach ($blocks as $block) {
            $html .= $this->renderBlock($block);
        }

        if ($unsubscribeUrl) {
            $html .= '<p style="margin:32px 0 0;text-align:center;font-size:12px;color:#9ca3af;">'
                   . 'You received this because you subscribed to our newsletter. '
                   . '<a href="' . htmlspecialchars($unsubscribeUrl) . '" style="color:#9ca3af;">Unsubscribe</a>'
                   . '</p>';
        }

        $html .= '</td></tr></table></td></tr></table></body></html>';

        return $html;
    }

    private function renderBlock(array $block): string
    {
        $type = $block['type'] ?? '';
        $data = $block['data'] ?? [];

        return match ($type) {
            'paragraph'  => $this->paragraph($data),
            'heading'    => $this->heading($data),
            'image'      => $this->image($data),
            'divider'    => $this->divider(),
            'button'     => $this->button($data),
            'cta'        => $this->cta($data),
            'html'       => $this->rawHtml($data),
            'quote'      => $this->quote($data),
            'alert'      => $this->alert($data),
            'spacer'     => $this->spacer($data),
            'list'       => $this->listBlock($data),
            'container', 'section' => $this->renderChildren($block),
            default      => '',
        };
    }

    private function renderChildren(array $block): string
    {
        $html = '';
        foreach ($block['children'] ?? [] as $child) {
            $html .= $this->renderBlock($child);
        }
        return $html;
    }

    private function paragraph(array $data): string
    {
        $text = $this->esc($data['content'] ?? '');
        if (empty(trim(strip_tags($text)))) return '';
        return '<p style="margin:0 0 16px;font-size:16px;line-height:1.7;color:#1a1a1a;">' . $text . '</p>';
    }

    private function heading(array $data): string
    {
        $text  = $this->esc($data['text'] ?? '');
        if (empty($text)) return '';
        $level = max(1, min(6, (int) ($data['level'] ?? 2)));
        $sizes = [1 => '28px', 2 => '24px', 3 => '20px', 4 => '18px', 5 => '16px', 6 => '14px'];
        $size  = $sizes[$level];
        return "<h{$level} style=\"margin:24px 0 12px;font-size:{$size};font-weight:700;color:#111827;line-height:1.3;\">{$text}</h{$level}>";
    }

    private function image(array $data): string
    {
        $url = $this->esc($data['url'] ?? '');
        if (empty($url)) return '';
        $alt = $this->esc($data['alt'] ?? '');
        $cap = $this->esc($data['caption'] ?? '');
        $html = '<p style="margin:0 0 16px;text-align:center;">'
              . '<img src="' . $url . '" alt="' . $alt . '" style="max-width:100%;height:auto;border-radius:4px;" />'
              . '</p>';
        if ($cap) {
            $html .= '<p style="margin:-8px 0 16px;text-align:center;font-size:13px;color:#6b7280;">' . $cap . '</p>';
        }
        return $html;
    }

    private function divider(): string
    {
        return '<hr style="border:none;border-top:1px solid #e5e7eb;margin:24px 0;" />';
    }

    private function button(array $data): string
    {
        $label = $this->esc($data['label'] ?? 'Click here');
        $url   = $this->esc($data['url'] ?? '#');
        $align = $data['align'] ?? 'center';
        return '<p style="margin:0 0 16px;text-align:' . $align . ';">'
             . '<a href="' . $url . '" style="display:inline-block;background:#5e81ac;color:#ffffff;padding:12px 28px;border-radius:6px;text-decoration:none;font-weight:600;font-size:15px;">'
             . $label . '</a></p>';
    }

    private function cta(array $data): string
    {
        $html = '';
        if (!empty($data['headline'])) {
            $html .= '<h2 style="margin:0 0 8px;font-size:22px;font-weight:700;color:#111827;">' . $this->esc($data['headline']) . '</h2>';
        }
        if (!empty($data['text'])) {
            $html .= '<p style="margin:0 0 16px;font-size:16px;line-height:1.7;color:#374151;">' . $this->esc($data['text']) . '</p>';
        }
        if (!empty($data['button_url'])) {
            $label = $this->esc($data['button_label'] ?? 'Learn more');
            $url   = $this->esc($data['button_url']);
            $html .= '<p style="margin:0 0 16px;">'
                   . '<a href="' . $url . '" style="display:inline-block;background:#5e81ac;color:#ffffff;padding:12px 28px;border-radius:6px;text-decoration:none;font-weight:600;font-size:15px;">'
                   . $label . '</a></p>';
        }
        return $html;
    }

    private function rawHtml(array $data): string
    {
        return $data['content'] ?? '';
    }

    private function quote(array $data): string
    {
        $text = $this->esc($data['text'] ?? '');
        if (empty($text)) return '';
        $html = '<blockquote style="margin:0 0 16px;padding:12px 20px;border-left:4px solid #5e81ac;background:#f0f4f8;border-radius:0 4px 4px 0;">'
              . '<p style="margin:0;font-size:16px;line-height:1.7;color:#374151;font-style:italic;">' . $text . '</p>';
        if (!empty($data['attribution'])) {
            $html .= '<p style="margin:8px 0 0;font-size:13px;color:#6b7280;">— ' . $this->esc($data['attribution']) . '</p>';
        }
        $html .= '</blockquote>';
        return $html;
    }

    private function alert(array $data): string
    {
        $msg   = $this->esc($data['message'] ?? '');
        if (empty($msg)) return '';
        $bgMap = ['info' => '#eff6ff', 'success' => '#f0fdf4', 'warning' => '#fffbeb', 'error' => '#fef2f2'];
        $bg    = $bgMap[$data['type'] ?? 'info'] ?? '#eff6ff';
        return '<div style="margin:0 0 16px;padding:12px 16px;background:' . $bg . ';border-radius:6px;">'
             . '<p style="margin:0;font-size:15px;line-height:1.6;color:#1a1a1a;">' . $msg . '</p></div>';
    }

    private function spacer(array $data): string
    {
        $h = $data['height']['default'] ?? '16px';
        return '<div style="height:' . $this->esc($h) . ';"></div>';
    }

    private function listBlock(array $data): string
    {
        $items = $data['items'] ?? [];
        if (empty($items)) return '';
        $ordered = ($data['style'] ?? 'unordered') === 'ordered';
        $tag     = $ordered ? 'ol' : 'ul';
        $html    = "<{$tag} style=\"margin:0 0 16px;padding-left:24px;\">";
        foreach ($items as $item) {
            $text  = $this->esc(is_array($item) ? ($item['text'] ?? '') : $item);
            $html .= '<li style="margin-bottom:6px;font-size:16px;line-height:1.6;color:#1a1a1a;">' . $text . '</li>';
        }
        $html .= "</{$tag}>";
        return $html;
    }

    private function esc(string $str): string
    {
        return htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
