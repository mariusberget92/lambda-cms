import { inject, computed } from 'vue'

/**
 * Resolve a block field, preferring the loop item binding when inside a Loop block.
 *
 * @param {() => Object} getBlock   - getter for the block prop (e.g. () => props.block)
 * @param {string}       fieldName  - the block.data field name (e.g. 'text', 'url', 'content')
 * @returns {ComputedRef<any>}
 *
 * How it works:
 *   1. injects 'loopItem' from the nearest LoopItemProvider ancestor (null if not inside a loop)
 *   2. if block.bindings[fieldName] is set AND loopItem exists, returns loopItem[binding]
 *   3. otherwise falls back to block.data[fieldName]
 */
export function useFieldBinding(getBlock, fieldName) {
  const loopItem = inject('loopItem', null)

  return computed(() => {
    const block   = getBlock()
    const binding = block?.bindings?.[fieldName]
    if (binding && loopItem?.value) {
      return loopItem.value[binding] ?? block?.data?.[fieldName]
    }
    return block?.data?.[fieldName]
  })
}
