# API Overview

Lambda CMS exposes a read-only REST API at `/api/v1/`. No authentication is required.

## Base URL

```
https://yourdomain.com/api/v1
```

## Endpoints

| Method | Endpoint | Description |
|---|---|---|
| GET | `/posts` | Paginated list of published posts |
| GET | `/posts/{slug}` | Single post by slug |
| GET | `/categories` | All categories |
| GET | `/categories/{slug}` | Single category |
| GET | `/tags` | All tags |
| GET | `/tags/{slug}` | Single tag |
| POST | `/query` | Dynamic query (used by the Loop block) |

## Response Format

All list responses follow this envelope:

```json
{
  "data": [...],
  "meta": {
    "current_page": 1,
    "last_page": 4,
    "per_page": 15,
    "total": 52
  }
}
```

Single-resource responses return the object directly.

## Errors

| Status | Meaning |
|---|---|
| `404` | Resource not found |
| `422` | Validation error (query endpoint) |
| `500` | Server error |

Error responses include a `message` field:

```json
{ "message": "Post not found." }
```
