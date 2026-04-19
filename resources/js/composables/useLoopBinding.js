import { inject, computed } from 'vue'

/**
 * Resolve a block field, preferring a dynamic binding over the static data value.
 *
 * Binding value formats:
 *   'loop:title'        → loopItem.value.title
 *   'post:title'        → postContext.title
 *   'post:author_name'  → postContext.author?.name  (flattened nested field)
 *   'title'             → legacy — treated as 'loop:title'
 *
 * Falls back to block.data[fieldName] when no binding, or when the provider
 * is not available in the current component tree.
 */
export function useFieldBinding(getBlock, fieldName) {
  const loopItem    = inject('loopItem',    null)
  const postContext = inject('postContext', null)

  return computed(() => {
    const block    = getBlock()
    const binding  = block?.bindings?.[fieldName]
    const fallback = block?.data?.[fieldName]

    if (!binding) return fallback

    const colon = binding.indexOf(':')

    // Legacy: no prefix → treat as loop binding
    if (colon === -1) {
      return loopItem?.value?.[binding] ?? fallback
    }

    const source = binding.slice(0, colon)
    const field  = binding.slice(colon + 1)

    if (source === 'loop') {
      return loopItem?.value?.[field] ?? fallback
    }

    if (source === 'post') {
      return resolvePostField(postContext, field) ?? fallback
    }

    return fallback
  })
}

/**
 * Resolve a field from the postContext object.
 * Handles flattened keys that map to nested paths on the context object.
 */
function resolvePostField(postContext, field) {
  const ctx = postContext  // postContext is a plain object (not a ref) from TemplatePage.provide
  if (!ctx) return undefined

  // Nested field mappings
  const nested = {
    author_name:       c => c.author?.name,
    author_avatar_url: c => c.author?.avatar_url,
  }

  if (nested[field]) return nested[field](ctx)
  return ctx[field]
}
