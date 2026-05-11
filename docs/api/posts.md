# Posts API

## List posts

```
GET /api/v1/posts
```

Returns a paginated collection of published posts.

### Query parameters

| Parameter | Type | Description |
|---|---|---|
| `page` | integer | Page number (default: 1) |
| `per_page` | integer | Results per page (default: 15, max: 100) |
| `search` | string | Filter posts by title or body content |
| `category` | string | Filter by category slug |
| `tag` | string | Filter by tag slug |
| `order_by` | string | Sort field: `published_at` (default), `title`, `comment_count` |
| `order` | string | Sort direction: `desc` (default) or `asc` |

### Example request

```bash
curl "https://your-site.com/api/v1/posts?category=technology&per_page=5&order_by=published_at"
```

### Example response

```json
{
  "data": [
    {
      "id": 42,
      "title": "Getting Started with Lambda CMS",
      "slug": "getting-started-with-lambda-cms",
      "excerpt": "A quick introduction to installing and configuring Lambda CMS.",
      "featured_image": {
        "url": "https://your-site.com/storage/media/abc123.jpg",
        "alt": "Lambda CMS dashboard"
      },
      "author": {
        "name": "Jane Smith",
        "avatar": "https://your-site.com/storage/media/avatar.jpg"
      },
      "categories": [
        { "name": "Technology", "slug": "technology" }
      ],
      "tags": [
        { "name": "cms", "slug": "cms" },
        { "name": "php", "slug": "php" }
      ],
      "comment_count": 4,
      "reading_time": 3,
      "published_at": "2024-11-15T14:32:00Z",
      "url": "https://your-site.com/blog/getting-started-with-lambda-cms"
    }
  ],
  "links": { ... },
  "meta": { ... }
}
```

## Get a single post

```
GET /api/v1/posts/{slug}
```

Returns the full post including body content.

### Example request

```bash
curl "https://your-site.com/api/v1/posts/getting-started-with-lambda-cms"
```

### Example response

```json
{
  "data": {
    "id": 42,
    "title": "Getting Started with Lambda CMS",
    "slug": "getting-started-with-lambda-cms",
    "excerpt": "A quick introduction...",
    "body": "<p>Lambda CMS is a modern...</p>",
    "featured_image": { ... },
    "author": { ... },
    "categories": [ ... ],
    "tags": [ ... ],
    "meta": {
      "title": "Getting Started with Lambda CMS · My Blog",
      "description": "A quick introduction to installing and configuring Lambda CMS.",
      "keywords": "cms, php, laravel"
    },
    "comment_count": 4,
    "reading_time": 3,
    "published_at": "2024-11-15T14:32:00Z",
    "url": "https://your-site.com/blog/getting-started-with-lambda-cms"
  }
}
```

::: info
The `body` field returns rendered HTML. For block-editor posts, the full block tree is rendered server-side and returned as HTML. Rich-text posts return the stored HTML directly.
:::
