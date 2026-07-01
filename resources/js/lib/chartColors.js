export const CHART_COLORS = [
  { bg: 'color-mix(in srgb, var(--color-chart-1) 15%, transparent)', border: 'color-mix(in srgb, var(--color-chart-1) 40%, transparent)', text: 'var(--color-chart-1)' },
  { bg: 'color-mix(in srgb, var(--color-chart-2) 15%, transparent)', border: 'color-mix(in srgb, var(--color-chart-2) 40%, transparent)', text: 'var(--color-chart-2)' },
  { bg: 'color-mix(in srgb, var(--color-chart-3) 15%, transparent)', border: 'color-mix(in srgb, var(--color-chart-3) 40%, transparent)', text: 'var(--color-chart-3)' },
  { bg: 'color-mix(in srgb, var(--color-chart-4) 15%, transparent)', border: 'color-mix(in srgb, var(--color-chart-4) 40%, transparent)', text: 'var(--color-chart-4)' },
  { bg: 'color-mix(in srgb, var(--color-chart-5) 15%, transparent)', border: 'color-mix(in srgb, var(--color-chart-5) 40%, transparent)', text: 'var(--color-chart-5)' },
]

export function bubbleStyle(item, index, maxCount) {
  const ratio = maxCount > 0 ? item.posts_count / maxCount : 0
  const px = 10 + ratio * 22
  const py = 5 + ratio * 11
  const fontSize = 11 + ratio * 8
  const color = CHART_COLORS[index % CHART_COLORS.length]
  return {
    paddingLeft: `${px}px`, paddingRight: `${px}px`,
    paddingTop: `${py}px`, paddingBottom: `${py}px`,
    fontSize: `${fontSize}px`,
    backgroundColor: color.bg, borderColor: color.border, color: color.text,
  }
}
