<template>
  <div class="code-block">
    <div class="code-block__header">
      <span>{{ block.data.language || 'code' }}</span>
    </div>
    <pre class="code-block__pre"><code>{{ block.data.code }}</code></pre>
  </div>
</template>

<script setup>
defineProps({ block: { type: Object, required: true } })
</script>

<style scoped>
.code-block {
  margin: 1rem 0;
  border-radius: var(--blog-radius);
  overflow: hidden;
  border: 1px solid var(--line-strong);
}
.code-block__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.375rem 0.75rem;
  background: var(--bg);
  color: var(--soft);
  font-size: 0.75rem;
  font-family: 'JetBrains Mono', ui-monospace, monospace;
  border-bottom: 1px solid var(--line-strong);
}
.code-block__pre {
  padding: 1rem;
  overflow-x: auto;
  font-size: 0.875rem;
  background: var(--code);
  color: var(--code-ink);
  margin: 0;
  font-family: 'JetBrains Mono', ui-monospace, monospace;
}
</style>
