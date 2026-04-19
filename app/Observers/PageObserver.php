<?php

namespace App\Observers;

use App\Models\Page;
use App\Services\WebhookService;

class PageObserver
{
    public function __construct(private WebhookService $webhooks) {}

    public function updated(Page $page): void
    {
        $event = $page->wasChanged('status') && $page->status === 'published'
            ? 'page.published'
            : 'page.updated';

        $this->webhooks->dispatch($event, $this->payload($page));
    }

    public function deleted(Page $page): void
    {
        $this->webhooks->dispatch('page.deleted', [
            'id'    => $page->id,
            'title' => $page->title,
            'slug'  => $page->slug,
        ]);
    }

    private function payload(Page $page): array
    {
        return [
            'id'     => $page->id,
            'title'  => $page->title,
            'slug'   => $page->slug,
            'status' => $page->status,
            'url'    => url("/{$page->slug}"),
        ];
    }
}
