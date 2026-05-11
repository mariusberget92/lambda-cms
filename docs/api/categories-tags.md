# Categories & Tags API

## List categories

```
GET /api/v1/categories
```

Returns all categories that have at least one published post.

### Example response

```json
{
  "data": [
    {
      "id": 1,
      "name": "Technology",
      "slug": "technology",
      "description": "Posts about software, hardware, and the tech industry.",
      "color": "#3b82f6",
      "post_count": 14,
      "url": "https://your-site.com/blog/category/technology"
    },
    {
      "id": 2,
      "name": "Travel",
      "slug": "travel",
      "description": null,
      "color": "#10b981",
      "post_count": 7,
      "url": "https://your-site.com/blog/category/travel"
    }
  ]
}
```

## Get a single category

```
GET /api/v1/categories/{slug}
```

Returns the category and its metadata. Does not include the list of posts — use the [Query Builder](/api/query-builder) or the [posts endpoint](/api/posts) with `?category={slug}` to fetch posts for a category.

### Example request

```bash
curl "https://your-site.com/api/v1/categories/technology"
```

### Example response

```json
{
  "data": {
    "id": 1,
    "name": "Technology",
    "slug": "technology",
    "description": "Posts about software, hardware, and the tech industry.",
    "color": "#3b82f6",
    "post_count": 14,
    "url": "https://your-site.com/blog/category/technology"
  }
}
```

## List tags

```
GET /api/v1/tags
```

Returns all tags that have at least one published post.

### Example response

```json
{
  "data": [
    {
      "id": 1,
      "name": "laravel",
      "slug": "laravel",
      "post_count": 9,
      "url": "https://your-site.com/blog/tag/laravel"
    }
  ]
}
```

## Get a single tag

```
GET /api/v1/tags/{slug}
```

```json
{
  "data": {
    "id": 1,
    "name": "laravel",
    "slug": "laravel",
    "post_count": 9,
    "url": "https://your-site.com/blog/tag/laravel"
  }
}
```
