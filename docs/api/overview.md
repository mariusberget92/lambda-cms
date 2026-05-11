# API Overview

Lambda CMS exposes a public read-only JSON REST API at `/api/v1/`. Use it to power a headless frontend, a mobile app, or any external tool that needs access to your content.

## Base URL

```
https://your-site.com/api/v1
```

## Authentication

The API is **public and unauthenticated**. Only published content is returned — drafts, scheduled posts, and private data are never exposed.

## Response format

All responses return JSON. Collections are paginated and follow this envelope:

```json
{
  "data": [ ... ],
  "links": {
    "first": "...",
    "last": "...",
    "prev": null,
    "next": "..."
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 5,
    "per_page": 15,
    "to": 15,
    "total": 72
  }
}
```

Single-resource responses return:

```json
{
  "data": { ... }
}
```

## Error responses

| HTTP status | Meaning |
|---|---|
| `404 Not Found` | Resource does not exist or is not published |
| `422 Unprocessable Entity` | Validation error (Query Builder endpoint) |
| `429 Too Many Requests` | Rate limit exceeded |

## Available endpoints

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/v1/posts` | Paginated list of published posts |
| `GET` | `/api/v1/posts/{slug}` | Single post by slug |
| `GET` | `/api/v1/categories` | All categories with post counts |
| `GET` | `/api/v1/categories/{slug}` | Single category |
| `GET` | `/api/v1/tags` | All tags |
| `GET` | `/api/v1/tags/{slug}` | Single tag |
| `POST` | `/api/v1/query` | Flexible Query Builder endpoint |

## Rate limiting

The API is rate-limited to **60 requests per minute** per IP address. The current limit and remaining count are included in the response headers:

```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 58
```

## CORS

CORS is enabled for all `/api/v1/*` routes. Adjust the allowed origins in `config/cors.php` if needed.
