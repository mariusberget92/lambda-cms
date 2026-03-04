import { ref } from 'vue'

const STORAGE_KEY = 'lambda-cms-theme'

// Module-level singleton so all consumers share the same state
const isDark = ref(false)

function applyTheme(dark) {
  isDark.value = dark
  if (dark) {
    document.documentElement.classList.add('dark')
  } else {
    document.documentElement.classList.remove('dark')
  }
  localStorage.setItem(STORAGE_KEY, dark ? 'dark' : 'light')
}

function initTheme() {
  const saved = localStorage.getItem(STORAGE_KEY)
  if (saved === 'dark' || saved === 'light') {
    applyTheme(saved === 'dark')
  } else {
    // Fall back to OS preference on first visit
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches
    applyTheme(prefersDark)
  }
}

export function useTheme() {
  return {
    isDark,
    initTheme,
    toggleTheme: () => applyTheme(!isDark.value),
    setTheme: (value) => applyTheme(value === 'dark'),
  }
}
