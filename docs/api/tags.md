# Tags API

## List Tags

```http
GET /api/v1/tags
```

Returns all tags ordered by name.

### Response

```json
{
  "data": [
    {
      "id": 1,
      "name": "laravel",
      "slug": "laravel",
      "posts_count": 4
    }
  ]
}
```

---

## Get Tag

```http
GET /api/v1/tags/{slug}
```

### Response

```json
{
  "id": 1,
  "name": "laravel",
  "slug": "laravel",
  "posts_count": 4
}
```
