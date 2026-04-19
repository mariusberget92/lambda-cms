// Resolves a Lucide icon name string to a Vue component
import * as LucideIcons from 'lucide-vue-next'
import { computed } from 'vue'

const SIZE_MAP = { xs: '12px', sm: '16px', md: '20px', lg: '24px', xl: '32px' }

export function useIconResolver(iconDataRef) {
  const iconComponent = computed(() => {
    const name = iconDataRef.value?.name
    if (!name) return null
    return LucideIcons[name] ?? null
  })

  const iconStyle = computed(() => {
    const d = iconDataRef.value ?? {}
    return {
      width:  SIZE_MAP[d.size ?? 'md'],
      height: SIZE_MAP[d.size ?? 'md'],
      color:  d.color && d.color !== 'inherit' ? d.color : undefined,
      flexShrink: 0,
    }
  })

  return { iconComponent, iconStyle }
}
