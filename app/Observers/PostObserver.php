<?php

namespace App\Observers;

use App\Models\Post;
use App\Services\WebhookService;

class PostObserver
{
    public function __construct(private WebhookService $webhooks) {}

    public function updated(Post $post): void
    {
        $event = $post->wasChanged('status') && $post->status === 'published'
            ? 'post.published'
            : 'post.updated';

        $this->webhooks->dispatch($event, $this->payload($post));
    }

    public function deleted(Post $post): void
    {
        $this->webhooks->dispatch('post.deleted', [
            'id'    => $post->id,
            'title' => $post->title,
            'slug'  => $post->slug,
        ]);
    }

    private function payload(Post $post): array
    {
        return [
            'id'           => $post->id,
            'title'        => $post->title,
            'slug'         => $post->slug,
            'status'       => $post->status,
            'published_at' => $post->published_at?->toIso8601String(),
            'url'          => url("/blog/{$post->slug}"),
        ];
    }
}
