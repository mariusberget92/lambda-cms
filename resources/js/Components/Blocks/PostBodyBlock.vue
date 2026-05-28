<script setup>
import { inject } from 'vue'
import BlockRenderer from '@/Components/BlockRenderer.vue'
const post = inject('postContext', null)
</script>

<template>
  <div v-if="post" class="post-body-content">
    <BlockRenderer v-if="post.use_block_editor && post.blocks?.length" :blocks="post.blocks" />
    <!-- Content sanitized server-side via the post/page model before storage -->
    <div
      v-else
      class="post-body-prose"
      :class="post.body_format === 'markdown' ? 'is-markdown' : ''"
      v-html="post.body"
    />
  </div>
  <div v-else class="post-body-content space-y-3">
    <div class="h-4 rounded post-body__skel w-full" />
    <div class="h-4 rounded post-body__skel w-5/6" />
    <div class="h-4 rounded post-body__skel w-4/6" />
    <div class="h-4 rounded post-body__skel w-full" />
    <div class="h-4 rounded post-body__skel w-3/4" />
  </div>
</template>

<style scoped>
.post-body-content {
  padding: 2rem 2.5rem;
}
.post-body__skel {
  background: var(--line-strong);
}

/* ── Base prose ──────────────────────────────────────────────────────────── */
.post-body-prose {
  color: var(--ink);
  line-height: 1.8;
  font-size: 1rem;
  max-width: 70ch;
}

/* Links */
.post-body-prose :deep(a) {
  color: var(--accent);
  text-decoration: underline;
  text-underline-offset: 2px;
}
.post-body-prose :deep(a:hover) { opacity: 0.75; }

/* Inline code */
.post-body-prose :deep(:not(pre) > code) {
  background: var(--bg);
  color: var(--ink);
  border: 1px solid var(--line-strong);
  border-radius: var(--blog-radius);
  padding: 0.15em 0.4em;
  font-size: 0.875em;
  font-family: 'JetBrains Mono', 'Fira Code', monospace;
}

/* Code blocks */
.post-body-prose :deep(pre) {
  background: var(--code);
  color: var(--code-ink);
  border-radius: var(--blog-radius);
  padding: 1.25rem 1.5rem;
  overflow-x: auto;
  font-size: 0.875em;
  line-height: 1.7;
  margin: 1.5rem 0;
}
.post-body-prose :deep(pre code) {
  background: none;
  border: none;
  padding: 0;
  font-size: inherit;
}

/* Blockquotes */
.post-body-prose :deep(blockquote) {
  border-left: 4px solid var(--accent);
  margin: 1.5rem 0;
  padding: 0.75rem 1.25rem;
  color: var(--soft);
  background: color-mix(in srgb, var(--accent) 5%, transparent);
  border-radius: 0 var(--blog-radius) var(--blog-radius) 0;
  font-style: italic;
}
.post-body-prose :deep(blockquote p) { margin: 0; }

/* ── Markdown-specific prose (headings, lists, tables, HR, images) ───────── */
.post-body-prose.is-markdown :deep(h1),
.post-body-prose.is-markdown :deep(h2),
.post-body-prose.is-markdown :deep(h3),
.post-body-prose.is-markdown :deep(h4),
.post-body-prose.is-markdown :deep(h5),
.post-body-prose.is-markdown :deep(h6) {
  color: var(--ink);
  font-weight: 700;
  line-height: 1.3;
  margin-top: 2em;
  margin-bottom: 0.6em;
}
.post-body-prose.is-markdown :deep(h1) { font-size: 2em;   border-bottom: 1px solid var(--line); padding-bottom: 0.3em; }
.post-body-prose.is-markdown :deep(h2) { font-size: 1.5em; border-bottom: 1px solid var(--line); padding-bottom: 0.25em; }
.post-body-prose.is-markdown :deep(h3) { font-size: 1.25em; }
.post-body-prose.is-markdown :deep(h4) { font-size: 1.1em; }
.post-body-prose.is-markdown :deep(h5),
.post-body-prose.is-markdown :deep(h6) { font-size: 1em; color: var(--soft); }

.post-body-prose.is-markdown :deep(p) {
  margin: 1em 0;
}

.post-body-prose.is-markdown :deep(ul),
.post-body-prose.is-markdown :deep(ol) {
  padding-left: 1.75em;
  margin: 1em 0;
}
.post-body-prose.is-markdown :deep(li) { margin: 0.35em 0; }
.post-body-prose.is-markdown :deep(ul)  { list-style-type: disc; }
.post-body-prose.is-markdown :deep(ol)  { list-style-type: decimal; }
.post-body-prose.is-markdown :deep(ul ul)  { list-style-type: circle; }
.post-body-prose.is-markdown :deep(ul ul ul) { list-style-type: square; }

/* Task lists (GFM) */
.post-body-prose.is-markdown :deep(li input[type="checkbox"]) {
  accent-color: var(--accent);
  margin-right: 0.4em;
}

.post-body-prose.is-markdown :deep(hr) {
  border: none;
  border-top: 1px solid var(--line-strong);
  margin: 2.5rem 0;
}

/* Tables (GFM) */
.post-body-prose.is-markdown :deep(table) {
  width: 100%;
  border-collapse: collapse;
  margin: 1.5rem 0;
  font-size: 0.9em;
}
.post-body-prose.is-markdown :deep(th),
.post-body-prose.is-markdown :deep(td) {
  border: 1px solid var(--line-strong);
  padding: 0.6em 0.9em;
  text-align: left;
}
.post-body-prose.is-markdown :deep(th) {
  background: var(--bg);
  font-weight: 600;
  color: var(--ink);
}
.post-body-prose.is-markdown :deep(tr:nth-child(even) td) {
  background: color-mix(in srgb, var(--panel) 60%, transparent);
}

/* Images */
.post-body-prose.is-markdown :deep(img) {
  max-width: 100%;
  height: auto;
  border-radius: var(--blog-radius);
  margin: 1.5rem 0;
  border: 1px solid var(--line-strong);
}

/* Strong / em */
.post-body-prose.is-markdown :deep(strong) { font-weight: 700; color: var(--ink); }
.post-body-prose.is-markdown :deep(em)     { font-style: italic; }
.post-body-prose.is-markdown :deep(del)    { text-decoration: line-through; color: var(--soft); }
</style>
