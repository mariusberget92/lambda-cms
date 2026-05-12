<!-- resources/js/Components/BlockEditor/blocks/SocialLinksSettings.vue -->
<template>
  <!-- Content tab -->
  <div v-show="!tab || tab === 'content'" class="space-y-3">
    <div>
      <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wide block mb-2">Links</label>
      <div class="space-y-2">
        <div v-for="(link, i) in (block.data.links ?? [])" :key="i" class="rounded border border-border p-2 space-y-1.5">
          <div class="flex items-center justify-between">
            <SelectBox size="sm" :model-value="link.platform ?? 'website'" :data="PLATFORMS"
              class="flex-1 mr-2"
              @update:model-value="v => updateLink(i, 'platform', v)" />
            <button type="button" class="text-muted-foreground hover:text-destructive transition-colors p-1" @click="removeLink(i)">
              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
          </div>
          <input :value="link.url" type="text" placeholder="https://..."
            class="w-full rounded-md border bg-background px-2 py-1 text-xs focus:outline-none focus:ring-2 focus:ring-ring"
            @input="updateLink(i, 'url', $event.target.value)" />
          <input :value="link.label" type="text" :placeholder="link.platform || 'Label (optional)'"
            class="w-full rounded-md border bg-background px-2 py-1 text-xs focus:outline-none focus:ring-2 focus:ring-ring"
            @input="updateLink(i, 'label', $event.target.value)" />
        </div>
      </div>
      <button type="button"
        class="mt-2 w-full py-1.5 text-xs rounded border border-dashed border-border text-muted-foreground hover:border-muted-foreground hover:text-foreground transition-colors"
        @click="addLink">+ Add link</button>
    </div>
  </div>

  <!-- Style tab -->
  <div v-show="!tab || tab === 'style'" class="space-y-3">
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Display style</label>
      <div class="flex rounded-md border overflow-hidden text-xs">
        <button v-for="[val, lbl] in [['icon-only', 'Icons'], ['icon-label', 'Icons + labels'], ['label-only', 'Labels']]" :key="val"
          type="button"
          class="flex-1 py-1.5 transition-colors"
          :class="(block.data.style ?? 'icon-only') === val ? 'bg-primary text-primary-foreground' : 'bg-background text-foreground'"
          @click="emit('update', { id: block.id, data: { style: val } })">
          {{ lbl }}
        </button>
      </div>
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Size</label>
      <div class="flex rounded-md border overflow-hidden text-xs">
        <button v-for="[val, lbl] in [['sm', 'S'], ['md', 'M'], ['lg', 'L']]" :key="val"
          type="button"
          class="flex-1 py-1.5 transition-colors"
          :class="(block.data.size ?? 'md') === val ? 'bg-primary text-primary-foreground' : 'bg-background text-foreground'"
          @click="emit('update', { id: block.id, data: { size: val } })">
          {{ lbl }}
        </button>
      </div>
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Alignment</label>
      <div class="flex rounded-md border overflow-hidden text-xs">
        <button v-for="al in ['left', 'center', 'right']" :key="al"
          type="button"
          class="flex-1 py-1.5 transition-colors capitalize"
          :class="(block.data.align ?? 'left') === al ? 'bg-primary text-primary-foreground' : 'bg-background text-foreground'"
          @click="emit('update', { id: block.id, data: { align: al } })">
          {{ al }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import SelectBox from '@/Components/SelectBox.vue'

const PLATFORMS = [
  { value: 'twitter',   label: 'Twitter / X' },
  { value: 'linkedin',  label: 'LinkedIn' },
  { value: 'github',    label: 'GitHub' },
  { value: 'facebook',  label: 'Facebook' },
  { value: 'instagram', label: 'Instagram' },
  { value: 'youtube',   label: 'YouTube' },
  { value: 'tiktok',    label: 'TikTok' },
  { value: 'discord',   label: 'Discord' },
  { value: 'twitch',    label: 'Twitch' },
  { value: 'pinterest', label: 'Pinterest' },
  { value: 'reddit',    label: 'Reddit' },
  { value: 'whatsapp',  label: 'WhatsApp' },
  { value: 'telegram',  label: 'Telegram' },
  { value: 'email',     label: 'Email' },
  { value: 'website',   label: 'Website' },
  { value: 'rss',       label: 'RSS' },
]

const props = defineProps({ block: { type: Object, required: true }, tab: { type: String, default: null } })
const emit = defineEmits(['update'])

function updateLink(i, key, value) {
  const links = [...(props.block.data.links ?? [])]
  links[i] = { ...links[i], [key]: value }
  emit('update', { id: props.block.id, data: { links } })
}
function removeLink(i) {
  const links = [...(props.block.data.links ?? [])]
  links.splice(i, 1)
  emit('update', { id: props.block.id, data: { links } })
}
function addLink() {
  const links = [...(props.block.data.links ?? []), { platform: 'website', url: '', label: '' }]
  emit('update', { id: props.block.id, data: { links } })
}
</script>
