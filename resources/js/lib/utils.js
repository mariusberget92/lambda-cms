import { clsx } from "clsx";
import { twMerge } from "tailwind-merge";

export function cn(...inputs) {
    return twMerge(clsx(inputs));
}

export function filterEmptyBlocks(blocks) {
  return (blocks ?? []).filter(b => {
    const d = b.data ?? {}
    return Object.values(d).some(v =>
      v !== null && v !== '' && !(Array.isArray(v) && v.length === 0)
    )
  })
}

export function decodeHtmlEntities(str) {
  const txt = document.createElement('textarea')
  txt.innerHTML = str
  return txt.value
}

/**
 * Format an ISO date string as a human-readable date.
 * e.g. "Mar 28, 2026"
 */
export function formatDate(isoString) {
  if (!isoString) return '—'
  // Normalize MySQL space-separated datetimes to ISO 8601
  const normalized = String(isoString).replace(' ', 'T')
  const d = new Date(normalized)
  if (isNaN(d.getTime())) return '—'
  return d.toLocaleDateString('en-US', {
    year: 'numeric', month: 'short', day: 'numeric',
  })
}

/**
 * Format an ISO date string as a human-readable date + time.
 * e.g. "Mar 28, 2026, 14:05"
 */
export function formatDateTime(isoString) {
  if (!isoString) return '—'
  // Normalize MySQL space-separated datetimes to ISO 8601
  const normalized = String(isoString).replace(' ', 'T')
  const d = new Date(normalized)
  if (isNaN(d.getTime())) return '—'
  return d.toLocaleString('en-US', {
    year: 'numeric', month: 'short', day: 'numeric',
    hour: '2-digit', minute: '2-digit',
  })
}
