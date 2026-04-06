<?php

namespace App\Jobs;

use App\Models\Webhook;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DispatchWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public readonly Webhook $webhook,
        public readonly string $event,
        public readonly array $payload,
    ) {}

    public function handle(): void
    {
        $body = json_encode([
            'event'     => $this->event,
            'payload'   => $this->payload,
            'timestamp' => now()->toIso8601String(),
        ]);

        $headers = ['Content-Type' => 'application/json'];

        if ($this->webhook->secret) {
            $headers['X-Lambda-Signature'] = 'sha256=' . hash_hmac('sha256', $body, $this->webhook->secret);
        }

        try {
            Http::withHeaders($headers)
                ->timeout(10)
                ->post($this->webhook->url, json_decode($body, true));

            $this->webhook->update(['last_triggered_at' => now()]);
        } catch (\Throwable $e) {
            Log::warning("Webhook delivery failed for {$this->webhook->url}: {$e->getMessage()}");
        }
    }
}
