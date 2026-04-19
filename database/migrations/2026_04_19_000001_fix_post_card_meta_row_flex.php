<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $template = DB::table('templates')
            ->where('title', 'Post Card')
            ->where('type', 'partial')
            ->first();

        if (!$template) return;

        $blocks = json_decode($template->blocks, true);
        if (!is_array($blocks)) return;

        $patched = $this->patchBlocks($blocks);

        DB::table('templates')
            ->where('id', $template->id)
            ->update(['blocks' => json_encode($patched)]);
    }

    private function patchBlocks(array $blocks): array
    {
        return array_map(function (array $block) {
            // Fix block 507: the meta+read-more row
            if (($block['id'] ?? null) === 507 && ($block['type'] ?? null) === 'container') {
                $block['data']['mode']    = 'flex';
                $block['data']['justify'] = 'between';
                unset($block['data']['childGrow']);
            }
            if (!empty($block['children'])) {
                $block['children'] = $this->patchBlocks($block['children']);
            }
            return $block;
        }, $blocks);
    }

    public function down(): void
    {
        $template = DB::table('templates')
            ->where('title', 'Post Card')
            ->where('type', 'partial')
            ->first();

        if (!$template) return;

        $blocks = json_decode($template->blocks, true);
        if (!is_array($blocks)) return;

        $reverted = $this->revertBlocks($blocks);

        DB::table('templates')
            ->where('id', $template->id)
            ->update(['blocks' => json_encode($reverted)]);
    }

    private function revertBlocks(array $blocks): array
    {
        return array_map(function (array $block) {
            if (($block['id'] ?? null) === 507 && ($block['type'] ?? null) === 'container') {
                $block['data']['mode']      = 'inline-flex';
                $block['data']['childGrow'] = false;
                unset($block['data']['justify']);
            }
            if (!empty($block['children'])) {
                $block['children'] = $this->revertBlocks($block['children']);
            }
            return $block;
        }, $blocks);
    }
};
