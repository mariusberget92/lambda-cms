# Webhooks

Webhooks let Lambda CMS notify external services when content changes. They are dispatched as queued HTTP POST requests.

## Events

| Event | Triggered when |
|---|---|
| `post.published` | A post's status changes to `published` |
| `post.updated` | A published post is updated |
| `post.deleted` | A post is deleted |
| `page.published` | A page's status changes to `published` |
| `page.updated` | A published page is updated |
| `page.deleted` | A page is deleted |

## Managing Webhooks

Go to **Settings → Webhooks** (admin-only). Each webhook has:

- **URL** — the endpoint to POST to
- **Events** — one or more events to listen for
- **Secret** — optional; when set, requests include a signature header

## Request Format

Lambda CMS sends a JSON POST request:

```http
POST https://your-endpoint.com/hook
Content-Type: application/json
X-Lambda-Event: post.published
X-Lambda-Signature: sha256=<hmac>

{
  "event": "post.published",
  "payload": {
    "id": 42,
    "title": "My Post",
    "slug": "my-post",
    "status": "published",
    "published_at": "2024-06-01T12:00:00Z",
    "url": "https://myblog.com/blog/my-post"
  }
}
```

## Verifying Signatures

When a secret is configured, Lambda CMS signs the raw request body using HMAC-SHA256 and sends the result as `X-Lambda-Signature: sha256=<hex>`.

To verify in your endpoint:

```php
$expected = 'sha256=' . hash_hmac('sha256', $rawBody, $secret);
$received = $request->header('X-Lambda-Signature');

if (!hash_equals($expected, $received)) {
    abort(401);
}
```

```javascript
const crypto = require('crypto')

const expected = 'sha256=' + crypto
  .createHmac('sha256', secret)
  .update(rawBody)
  .digest('hex')

if (expected !== received) {
  return res.status(401).end()
}
```

## Queue Requirement

Webhooks are dispatched via Laravel queued jobs. Ensure a queue worker is running in production:

```bash
php artisan queue:work
```

Without a running worker, webhooks will sit in the `jobs` table and never fire.
