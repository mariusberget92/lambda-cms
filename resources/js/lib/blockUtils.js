// resources/js/lib/blockUtils.js

/**
 * Converts a responsive value (or legacy flat string/number) into Tailwind class string.
 *
 * @param {string|number|object} value  - Plain value OR { default, sm, lg } object
 * @param {Function}             toClass - Maps a single value to a class string
 * @returns {string}
 *
 * Examples:
 *   resolveResponsive({ default: 3, sm: 1, lg: 4 }, v => `grid-cols-${v}`)
 *   → 'grid-cols-3 sm:grid-cols-1 lg:grid-cols-4'
 *
 *   resolveResponsive('row', v => v === 'column' ? 'flex-col' : 'flex-row')
 *   → 'flex-row'  (backward-compat: legacy flat string treated as default)
 */
export function resolveResponsive(value, toClass) {
  if (typeof value !== 'object' || value === null) {
    // Legacy flat value — no breakpoint prefix
    return toClass(value)
  }
  return Object.entries(value)
    .filter(([, v]) => v != null)
    .map(([bp, v]) => bp === 'default' ? toClass(v) : `${bp}:${toClass(v)}`)
    .join(' ')
}
