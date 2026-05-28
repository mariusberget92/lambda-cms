# Query Endpoint

```http
POST /api/v1/query
Content-Type: application/json
```

The query endpoint is the data source for the Loop block in the block editor. You can also use it directly to build dynamic UIs.

## Request Body

```json
{
  "source": "posts",
  "filters": [
    { "field": "category_slug", "op": "=", "value": "open-source" }
  ],
  "filter_logic": "and",
  "sort": { "field": "published_at", "direction": "desc" },
  "limit": 10,
  "offset": 0
}
```

| Field | Type | Description |
|---|---|---|
| `source` | string | `posts`, `categories`, `tags`, or `pages` |
| `filters` | array | Filter rules (see below) |
| `filter_logic` | string | `and` (default) or `or` |
| `sort` | object | `{ field, direction }` |
| `limit` | integer | Max items to return (default 10) |
| `offset` | integer | Items to skip for pagination |

## Filter Rules

Each filter rule:

```json
{ "field": "category_slug", "op": "=", "value": "open-source" }
```

Or with a URL param (value read from the current request URL):

```json
{ "field": "category_slug", "op": "=", "urlParam": "category" }
```

### Available Fields (posts)

| Field | Description |
|---|---|
| `category_slug` | Filter by category |
| `tag_slug` | Filter by tag |
| `title` | Filter by title (use `contains` op for search) |

### Operators

`=`, `!=`, `contains`, `starts_with`, `ends_with`

## Response

```json
{
  "items": [
    {
      "id": 1,
      "title": "Introducing Lambda CMS",
      "slug": "introducing-lambda-cms",
      "excerpt": "...",
      "url": "https://yourdomain.com/blog/introducing-lambda-cms",
      "featured_image_url": "...",
      "author_name": "Admin",
      "published_at": "2024-06-01T12:00:00Z",
      "published_at_formatted": "1 Jun 2024",
      "categories": [...],
      "tags": [...]
    }
  ],
  "total": 8,
  "per_page": 10,
  "current_page": 1,
  "last_page": 1
}
```
