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
            // Block 507: meta+read-more row — full-width flex with space-between
            if (($block['id'] ?? null) === 507 && ($block['type'] ?? null) === 'container') {
                $block['data']['mode'] = 'flex';
                $block['data']['justify'] = 'between';
                unset($block['data']['childGrow']);
            }
            // Block 502: inner content column — stretch so children fill card width
            if (($block['id'] ?? null) === 502 && ($block['type'] ?? null) === 'container') {
                $block['data']['align'] = 'stretch';
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
            ->where('title', 'Post Card')
            ->where('type', 'partial')
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
            if (($block['id'] ?? null) === 507 && ($block['type'] ?? null) === 'container') {
                $block['data']['mode'] = 'inline-flex';
                $block['data']['childGrow'] = false;
                unset($block['data']['justify']);
            }
            if (($block['id'] ?? null) === 502 && ($block['type'] ?? null) === 'container') {
                unset($block['data']['align']);
            }
            if (! empty($block['children'])) {
                $block['children'] = $this->revertBlocks($block['children']);
            }

            return $block;
        }, $blocks);
    }
};
