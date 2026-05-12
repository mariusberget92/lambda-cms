<!-- resources/js/Components/BlockEditor/blocks/TeamMemberSettings.vue -->
<template>
  <!-- Content tab -->
  <div v-show="!tab || tab === 'content'" class="space-y-3">
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Name</label>
      <input :value="block.data.name" type="text" placeholder="Jane Smith"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { name: $event.target.value } })" />
    </div>
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Role / Title</label>
      <input :value="block.data.role" type="text" placeholder="CEO & Co-founder"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { role: $event.target.value } })" />
    </div>
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Bio</label>
      <textarea :value="block.data.bio" rows="3" placeholder="Short bio..."
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring resize-none"
        @input="emit('update', { id: block.id, data: { bio: $event.target.value } })" />
    </div>
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Photo URL</label>
      <input :value="block.data.imageUrl" type="text" placeholder="https://..."
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { imageUrl: $event.target.value } })" />
    </div>
    <div>
      <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wide block mb-2">Social links</label>
      <div class="space-y-1.5">
        <div v-for="(sl, i) in (block.data.socialLinks ?? [])" :key="i" class="flex items-center gap-1.5">
          <SelectBox size="sm" :model-value="sl.platform ?? 'website'"
            :data="PLATFORMS" class="w-28 shrink-0"
            @update:model-value="v => updateSocial(i, 'platform', v)" />
          <input :value="sl.url" type="text" placeholder="https://..."
            class="flex-1 rounded-md border bg-background px-2 py-1 text-xs focus:outline-none focus:ring-2 focus:ring-ring"
            @input="updateSocial(i, 'url', $event.target.value)" />
          <button type="button" class="text-muted-foreground hover:text-destructive transition-colors p-1 shrink-0" @click="removeSocial(i)">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>
      </div>
      <button type="button"
        class="mt-2 w-full py-1.5 text-xs rounded border border-dashed border-border text-muted-foreground hover:border-muted-foreground hover:text-foreground transition-colors"
        @click="addSocial">+ Add social link</button>
    </div>
  </div>

  <!-- Style tab -->
  <div v-show="!tab || tab === 'style'" class="space-y-3">
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Layout</label>
      <div class="flex rounded-md border overflow-hidden text-xs">
        <button v-for="[val, lbl] in [['card', 'Card (vertical)'], ['horizontal', 'Horizontal']]" :key="val"
          type="button"
          class="flex-1 py-1.5 transition-colors"
          :class="(block.data.layout ?? 'card') === val ? 'bg-primary text-primary-foreground' : 'bg-background text-foreground'"
          @click="emit('update', { id: block.id, data: { layout: val } })">
          {{ lbl }}
        </button>
      </div>
    </div>
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Image shape <span class="opacity-60">(card layout)</span></label>
      <div class="flex rounded-md border overflow-hidden text-xs">
        <button v-for="[val, lbl] in [['circle', 'Circle'], ['square', 'Square']]" :key="val"
          type="button"
          class="flex-1 py-1.5 transition-colors"
          :class="(block.data.imageShape ?? 'circle') === val ? 'bg-primary text-primary-foreground' : 'bg-background text-foreground'"
          @click="emit('update', { id: block.id, data: { imageShape: val } })">
          {{ lbl }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import SelectBox from '@/Components/SelectBox.vue'

const PLATFORMS = [
  { value: 'linkedin', label: 'LinkedIn' }, { value: 'twitter', label: 'Twitter/X' },
  { value: 'github',   label: 'GitHub' },   { value: 'email',   label: 'Email' },
  { value: 'website',  label: 'Website' },
]

const props = defineProps({ block: { type: Object, required: true }, tab: { type: String, default: null } })
const emit = defineEmits(['update'])

function updateSocial(i, key, value) {
  const socialLinks = [...(props.block.data.socialLinks ?? [])]
  socialLinks[i] = { ...socialLinks[i], [key]: value }
  emit('update', { id: props.block.id, data: { socialLinks } })
}
function removeSocial(i) {
  const socialLinks = [...(props.block.data.socialLinks ?? [])]
  socialLinks.splice(i, 1)
  emit('update', { id: props.block.id, data: { socialLinks } })
}
function addSocial() {
  const socialLinks = [...(props.block.data.socialLinks ?? []), { platform: 'linkedin', url: '' }]
  emit('update', { id: props.block.id, data: { socialLinks } })
}
</script>
