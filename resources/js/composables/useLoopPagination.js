// resources/js/composables/useLoopPagination.js
import { reactive } from 'vue'

// Module-level store — keyed by pageParam string.
// Loop blocks write here after fetch; Pagination blocks read from here.
const store = reactive({})

export function useLoopPagination() {
  function setPagination(pageParam, total, perPage) {
    if (!pageParam) return
    store[pageParam] = { total, perPage }
  }

  function getPagination(pageParam) {
    return store[pageParam] ?? { total: 0, perPage: 1 }
  }

  return { setPagination, getPagination }
}
