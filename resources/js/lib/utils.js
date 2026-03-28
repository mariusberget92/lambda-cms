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
