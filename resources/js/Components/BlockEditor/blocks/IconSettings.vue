<!-- resources/js/Components/BlockEditor/blocks/IconSettings.vue -->
<template>
  <div class="space-y-3">
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Icon</label>
      <SelectBox
        :model-value="icon.name || ''"
        :searchable="true"
        placeholder="No icon"
        :data="[{ value: '', label: 'None' }, ...ICON_LIST.map(n => ({ value: n, label: n }))]"
        @update:model-value="v => update('name', v)"
      />
    </div>

    <template v-if="icon.name">
      <div>
        <label class="text-xs font-medium text-muted-foreground block mb-1">Position</label>
        <div class="flex gap-1">
          <button
            v-for="pos in ['left', 'right']"
            :key="pos"
            type="button"
            class="flex-1 px-2 py-1 text-xs rounded border capitalize transition-colors"
            :class="icon.position === pos
              ? 'bg-primary text-primary-foreground border-primary'
              : 'bg-background border-border hover:border-muted-foreground'"
            @click="update('position', pos)"
          >{{ pos }}</button>
        </div>
      </div>

      <div>
        <label class="text-xs font-medium text-muted-foreground block mb-1">Size</label>
        <div class="flex gap-1">
          <button
            v-for="sz in ['xs', 'sm', 'md', 'lg', 'xl']"
            :key="sz"
            type="button"
            class="flex-1 px-1 py-1 text-xs rounded border uppercase transition-colors"
            :class="icon.size === sz
              ? 'bg-primary text-primary-foreground border-primary'
              : 'bg-background border-border hover:border-muted-foreground'"
            @click="update('size', sz)"
          >{{ sz }}</button>
        </div>
      </div>

      <div>
        <label class="text-xs font-medium text-muted-foreground block mb-1">Color</label>
        <div class="flex items-center gap-2">
          <button
            type="button"
            class="px-2 py-1 text-xs rounded border transition-colors"
            :class="icon.color === 'inherit'
              ? 'bg-primary text-primary-foreground border-primary'
              : 'bg-background border-border'"
            @click="update('color', 'inherit')"
          >Inherit</button>
          <input
            type="color"
            :value="icon.color === 'inherit' ? '#000000' : icon.color"
            class="h-7 w-12 cursor-pointer rounded border border-border"
            @input="update('color', $event.target.value)"
          />
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import SelectBox from '@/Components/SelectBox.vue'

const props = defineProps({
  block: { type: Object, required: true },
})
const emit = defineEmits(['update'])

const icon = computed(() => props.block.data?.icon ?? { name: '', position: 'left', size: 'md', color: 'inherit' })

function update(key, value) {
  emit('update', {
    id: props.block.id,
    data: { icon: { ...icon.value, [key]: value } },
  })
}

const ICON_LIST = [
  'ArrowRight', 'ArrowLeft', 'ArrowUp', 'ArrowDown',
  'ArrowUpRight', 'ArrowDownRight', 'ExternalLink', 'Link',
  'ChevronRight', 'ChevronLeft', 'ChevronDown', 'ChevronUp',
  'Star', 'Heart', 'Bookmark', 'Share2', 'Download', 'Upload',
  'Mail', 'Phone', 'MapPin', 'Globe', 'Clock', 'Calendar',
  'User', 'Users', 'UserCircle', 'UserPlus',
  'Home', 'Building', 'Building2', 'ShoppingCart', 'ShoppingBag',
  'Search', 'Filter', 'Settings', 'Sliders', 'MoreHorizontal',
  'Plus', 'Minus', 'X', 'Check', 'CheckCircle', 'XCircle',
  'Info', 'AlertCircle', 'AlertTriangle', 'HelpCircle',
  'Zap', 'Flame', 'Shield', 'Lock', 'Unlock', 'Key',
  'Image', 'Video', 'Music', 'Play', 'Pause', 'Volume2',
  'FileText', 'File', 'Folder', 'Tag', 'Layers', 'Layout',
  'Code', 'Github', 'Twitter', 'Facebook', 'Instagram', 'Linkedin', 'Youtube',
  'Send', 'MessageCircle', 'MessageSquare', 'Bell', 'BellRing',
  'Cpu', 'Server', 'Database', 'Cloud', 'Wifi',
  'Sun', 'Moon', 'Leaf', 'Smile',
]
</script>
