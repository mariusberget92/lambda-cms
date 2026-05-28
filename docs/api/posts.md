# Posts API

## List Posts

```http
GET /api/v1/posts
```

### Query Parameters

| Param | Type | Description |
|---|---|---|
| `page` | integer | Page number (default: 1) |
| `per_page` | integer | Items per page (max 50, default 15) |
| `category` | string | Filter by category slug |
| `tag` | string | Filter by tag slug |
| `q` | string | Full-text search (title, excerpt, body) |

### Example

```http
GET /api/v1/posts?category=open-source&per_page=5
```

### Response

```json
{
  "data": [
    {
      "id": 1,
      "title": "Introducing Lambda CMS",
      "slug": "introducing-lambda-cms",
      "excerpt": "Lambda CMS is an open-source blog CMS...",
      "featured_image_url": "https://yourdomain.com/storage/media/2024/01/abc.jpg",
      "author_name": "Admin",
      "published_at": "2024-06-01T12:00:00Z",
      "published_at_formatted": "1 Jun 2024",
      "url": "https://yourdomain.com/blog/introducing-lambda-cms",
      "category_name": "Open Source",
      "categories": [{ "id": 1, "name": "Open Source", "slug": "open-source" }],
      "tags": [{ "id": 1, "name": "laravel", "slug": "laravel" }]
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 2,
    "per_page": 5,
    "total": 8
  }
}
```

---

## Get Post

```http
GET /api/v1/posts/{slug}
```

Returns the full post including rendered `body` HTML.

### Response

```json
{
  "id": 1,
  "title": "Introducing Lambda CMS",
  "slug": "introducing-lambda-cms",
  "excerpt": "...",
  "body": "<p>We built Lambda CMS because...</p>",
  "featured_image_url": "...",
  "author_name": "Admin",
  "published_at": "2024-06-01T12:00:00Z",
  "published_at_formatted": "1 Jun 2024",
  "url": "...",
  "categories": [...],
  "tags": [...]
}
```
