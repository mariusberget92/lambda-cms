# Query Builder

The Query Builder endpoint provides a flexible, composable way to query posts with arbitrary filters, sorting, and limits. It is the same data source used internally by [Loop Posts](/block-editor/dynamic-blocks#loop-posts) blocks in the block editor.

## Endpoint

```
POST /api/v1/query
Content-Type: application/json
```

## Request body

```json
{
  "source": "posts",
  "filters": [...],
  "sort": { "field": "published_at", "direction": "desc" },
  "limit": 10,
  "page": 1
}
```

### `source`

Required. The data source to query:

| Value | Description |
|---|---|
| `posts` | Published posts |
| `category_posts` | Posts filtered to a specific category |
| `tag_posts` | Posts filtered to a specific tag |
| `search_results` | Posts matching a search query |

### `filters`

An array of filter objects. Each filter has:

| Field | Type | Description |
|---|---|---|
| `field` | string | The field to filter on (see fields below) |
| `operator` | string | `=`, `!=`, `like`, `in`, `not_in` |
| `value` | mixed | The value to compare against |

#### Filterable fields

| Field | Type | Example value |
|---|---|---|
| `category` | string | `"technology"` (slug) |
| `tag` | string | `"laravel"` (slug) |
| `author` | integer | `1` (user ID) |
| `search` | string | `"headless cms"` |
| `published_at` | string | `"2024-01-01"` (ISO date, used with `>=` or `<=`) |

### `sort`

| Field | Description |
|---|---|
| `field` | `published_at`, `title`, `comment_count` |
| `direction` | `asc` or `desc` |

### `limit`

Maximum number of results to return. Default: `15`. Max: `100`.

### `page`

Page number for pagination. Default: `1`.

## Example: Recent posts in a category

```bash
curl -X POST "https://your-site.com/api/v1/query" \
  -H "Content-Type: application/json" \
  -d '{
    "source": "posts",
    "filters": [
      { "field": "category", "operator": "=", "value": "technology" }
    ],
    "sort": { "field": "published_at", "direction": "desc" },
    "limit": 5
  }'
```

## Example: Search with pagination

```bash
curl -X POST "https://your-site.com/api/v1/query" \
  -H "Content-Type: application/json" \
  -d '{
    "source": "search_results",
    "filters": [
      { "field": "search", "operator": "like", "value": "laravel api" }
    ],
    "sort": { "field": "published_at", "direction": "desc" },
    "limit": 10,
    "page": 2
  }'
```

## Response

The response follows the same paginated envelope as the [posts endpoint](/api/posts):

```json
{
  "data": [ ... ],
  "links": { ... },
  "meta": {
    "current_page": 1,
    "total": 23,
    "per_page": 10,
    ...
  }
}
```

Each item in `data` is a post object with the same fields as `GET /api/v1/posts`.

## Validation errors

If the request body is invalid, the endpoint returns a `422` with details:

```json
{
  "message": "The source field is required.",
  "errors": {
    "source": ["The source field is required."]
  }
}
```
