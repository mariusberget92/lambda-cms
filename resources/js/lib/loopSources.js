// Shared constants for Loop block data sources, fields, operators.
// Used by LoopSettings.vue, BlockEditor.vue (ancestry), LoopBlock.vue.

export const SOURCES = [
  { value: 'posts',      label: 'Posts' },
  { value: 'categories', label: 'Categories' },
  { value: 'tags',       label: 'Tags' },
  { value: 'pages',      label: 'Pages' },
]

// Fields exposed per loop source — used for DynamicField binding AND ConditionSettings.
// Values are UN-prefixed (used as loop item keys at runtime).
export const SOURCE_FIELDS = {
  posts: [
    { value: 'title',              label: 'Post Title' },
    { value: 'slug',               label: 'Post Slug' },
    { value: 'excerpt',            label: 'Excerpt' },
    { value: 'body',               label: 'Body Content' },
    { value: 'featured',           label: 'Is Featured' },
    { value: 'published_at',       label: 'Published Date' },
    { value: 'author_name',        label: 'Author' },
    { value: 'featured_image_url', label: 'Featured Image' },
    { value: 'url',                label: 'Post URL' },
  ],
  categories: [
    { value: 'name',        label: 'Category Name' },
    { value: 'slug',        label: 'Category Slug' },
    { value: 'description', label: 'Description' },
    { value: 'posts_count', label: 'Post Count' },
    { value: 'url',         label: 'Category URL' },
  ],
  tags: [
    { value: 'name',        label: 'Tag Name' },
    { value: 'slug',        label: 'Tag Slug' },
    { value: 'posts_count', label: 'Post Count' },
    { value: 'url',         label: 'Tag URL' },
  ],
  pages: [
    { value: 'title',            label: 'Page Title' },
    { value: 'slug',             label: 'Page Slug' },
    { value: 'meta_description', label: 'Meta Description' },
    { value: 'url',              label: 'Page URL' },
  ],
}

// Fields available from postContext (single-post template or post with block editor).
// Values ARE pre-prefixed with 'post:' so they can be stored directly as binding values.
export const POST_CONTEXT_FIELDS = [
  { value: 'post:title',              label: 'Post Title' },
  { value: 'post:slug',               label: 'Post Slug' },
  { value: 'post:excerpt',            label: 'Excerpt' },
  { value: 'post:body',               label: 'Body Content' },
  { value: 'post:published_at',       label: 'Published Date' },
  { value: 'post:author_name',        label: 'Author Name' },
  { value: 'post:author_avatar_url',  label: 'Author Avatar' },
  { value: 'post:featured_image_url', label: 'Featured Image' },
  { value: 'post:url',                label: 'Post URL' },
]

export const SORT_FIELDS = {
  posts:      ['published_at', 'title', 'created_at'],
  categories: ['name', 'created_at', 'posts_count'],
  tags:       ['name', 'created_at', 'posts_count'],
  pages:      ['title', 'created_at', 'updated_at'],
}

export const FILTER_OPS = [
  { value: '=',         label: 'Equals' },
  { value: '!=',        label: 'Not equals' },
  { value: 'contains',  label: 'Contains' },
  { value: 'not_empty', label: 'Is not empty' },
  { value: 'empty',     label: 'Is empty' },
]
