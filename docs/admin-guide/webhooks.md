# Webhooks

Webhooks let Lambda CMS notify external services when content changes. Each webhook sends an HTTP `POST` request to a URL you specify whenever a matching event fires.

## Supported events

| Event | Fires when |
|---|---|
| `post.published` | A post is published (status changes to published) |
| `post.updated` | A published post is updated |
| `post.deleted` | A post is deleted |
| `page.published` | A page is published |
| `page.updated` | A published page is updated |
| `page.deleted` | A page is deleted |

## Creating a webhook

Go to **Webhooks → New Webhook**:

1. **Event** — select the event that should trigger this webhook.
2. **URL** — the endpoint that will receive the POST request.
3. **Secret** (optional) — a string used to sign the request payload with HMAC-SHA256.

## Request format

Lambda CMS sends a JSON payload to your endpoint:

```json
{
  "event": "post.published",
  "timestamp": "2024-11-15T14:32:00Z",
  "data": {
    "id": 42,
    "title": "My Post Title",
    "slug": "my-post-title",
    "status": "published",
    "published_at": "2024-11-15T14:32:00Z"
  }
}
```

The exact fields in `data` depend on the event type (post or page).

## Request signing

When a secret is configured, every webhook request includes a `X-Lambda-Signature` header containing an HMAC-SHA256 signature of the raw request body, keyed by the secret:

```
X-Lambda-Signature: sha256=<hex-digest>
```

Verify the signature on your endpoint to confirm the request originated from your Lambda CMS instance:

```php
$expected = 'sha256=' . hash_hmac('sha256', $rawBody, $secret);
$valid = hash_equals($expected, $request->header('X-Lambda-Signature'));
```

## Last triggered

The webhook list shows the `last_triggered_at` timestamp for each webhook so you can confirm deliveries are firing as expected.

## Delivery and retries

Webhook delivery is handled by the `DispatchWebhookJob` queue job. With `QUEUE_CONNECTION=sync`, jobs run inline during the request. For reliable delivery in production, use a persistent queue driver (`database` or `redis`) so failed deliveries can be retried by the queue worker.
