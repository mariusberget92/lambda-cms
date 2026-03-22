// Shared constants for Loop block data sources, fields, operators.
// Used by LoopSettings.vue, BlockEditor.vue (ancestry), LoopBlock.vue.

export const SOURCES = [
  { value: 'posts',      label: 'Posts' },
  { value: 'categories', label: 'Categories' },
  { value: 'tags',       label: 'Tags' },
  { value: 'pages',      label: 'Pages' },
]

export const SOURCE_FIELDS = {
  posts:      ['title', 'slug', 'excerpt', 'body', 'featured', 'published_at', 'author_name', 'featured_image_url', 'url'],
  categories: ['name', 'slug', 'description', 'posts_count', 'url'],
  tags:       ['name', 'slug', 'posts_count', 'url'],
  pages:      ['title', 'slug', 'meta_description', 'url'],
}

export const SORT_FIELDS = {
  posts:      ['published_at', 'title', 'created_at'],
  categories: ['name', 'created_at', 'posts_count'],
  tags:       ['name', 'created_at', 'posts_count'],
  pages:      ['title', 'created_at', 'updated_at'],
}

export const FILTER_OPS = [
  { value: '=',         label: 'Equals' },
  { value: '!=',        label: 'Not equals' },
  { value: 'not_empty', label: 'Is not empty' },
  { value: 'empty',     label: 'Is empty' },
]
