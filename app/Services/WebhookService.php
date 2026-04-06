<?php

namespace App\Services;

use App\Jobs\DispatchWebhookJob;
use App\Models\Webhook;

class WebhookService
{
    public function dispatch(string $event, array $payload): void
    {
        Webhook::where('is_active', true)
            ->whereJsonContains('events', $event)
            ->each(function (Webhook $webhook) use ($event, $payload) {
                DispatchWebhookJob::dispatch($webhook, $event, $payload);
            });
    }
}
