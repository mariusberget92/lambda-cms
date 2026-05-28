# Categories API

## List Categories

```http
GET /api/v1/categories
```

Returns all categories ordered by name.

### Response

```json
{
  "data": [
    {
      "id": 1,
      "name": "Open Source",
      "slug": "open-source",
      "description": "Open source software, community, and collaboration.",
      "color": "#88c0d0",
      "posts_count": 3
    }
  ]
}
```

---

## Get Category

```http
GET /api/v1/categories/{slug}
```

### Response

```json
{
  "id": 1,
  "name": "Open Source",
  "slug": "open-source",
  "description": "Open source software, community, and collaboration.",
  "color": "#88c0d0",
  "posts_count": 3
}
```
