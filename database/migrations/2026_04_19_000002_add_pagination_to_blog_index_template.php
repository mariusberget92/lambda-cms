<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $template = DB::table('templates')
            ->where('title', 'Default Blog Index')
            ->where('type', 'blog-index')
            ->first();

        if (! $template) {
            return;
        }

        $blocks = json_decode($template->blocks, true);
        if (! is_array($blocks)) {
            return;
        }

        DB::table('templates')
            ->where('id', $template->id)
            ->update(['blocks' => json_encode($this->patchBlocks($blocks))]);
    }

    private function patchBlocks(array $blocks): array
    {
        return array_map(function (array $block) {
            if (($block['id'] ?? null) === 5 && ($block['type'] ?? null) === 'loop') {
                $block['data']['pageParam'] = 'page';
            }
            if (($block['id'] ?? null) === 3 && ($block['type'] ?? null) === 'container') {
                $hasPagination = collect($block['children'] ?? [])->contains(fn ($c) => ($c['id'] ?? null) === 6);
                if (! $hasPagination) {
                    $block['children'][] = [
                        'id' => 6,
                        'type' => 'pagination',
                        'data' => [
                            'pageParam' => 'page',
                            'style' => 'numbered',
                            'alignment' => 'center',
                            'buttonStyle' => 'outline',
                        ],
                    ];
                }
            }
            if (! empty($block['children'])) {
                $block['children'] = $this->patchBlocks($block['children']);
            }

            return $block;
        }, $blocks);
    }

    public function down(): void
    {
        $template = DB::table('templates')
            ->where('title', 'Default Blog Index')
            ->where('type', 'blog-index')
            ->first();

        if (! $template) {
            return;
        }

        $blocks = json_decode($template->blocks, true);
        if (! is_array($blocks)) {
            return;
        }

        DB::table('templates')
            ->where('id', $template->id)
            ->update(['blocks' => json_encode($this->revertBlocks($blocks))]);
    }

    private function revertBlocks(array $blocks): array
    {
        return array_map(function (array $block) {
            if (($block['id'] ?? null) === 5 && ($block['type'] ?? null) === 'loop') {
                unset($block['data']['pageParam']);
            }
            if (($block['id'] ?? null) === 3 && ($block['type'] ?? null) === 'container') {
                $block['children'] = array_values(
                    array_filter($block['children'] ?? [], fn ($c) => ($c['id'] ?? null) !== 6)
                );
            }
            if (! empty($block['children'])) {
                $block['children'] = $this->revertBlocks($block['children']);
            }

            return $block;
        }, $blocks);
    }
};
