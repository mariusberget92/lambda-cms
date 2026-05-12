<!-- resources/js/Components/BlockEditor/blocks/CountdownSettings.vue -->
<template>
  <!-- Content tab -->
  <div v-show="!tab || tab === 'content'" class="space-y-3">
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Title <span class="opacity-60">(optional)</span></label>
      <input :value="block.data.title" type="text" placeholder="Launching soon..."
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { title: $event.target.value } })" />
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Target date & time</label>
      <input :value="block.data.targetDate" type="datetime-local"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @change="emit('update', { id: block.id, data: { targetDate: $event.target.value } })" />
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Expired message</label>
      <input :value="block.data.expiredMessage" type="text" placeholder="The event has started!"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { expiredMessage: $event.target.value } })" />
    </div>

    <div>
      <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wide block mb-2">Show units</label>
      <div class="grid grid-cols-2 gap-1.5">
        <label v-for="[key, lbl] in [['showDays', 'Days'], ['showHours', 'Hours'], ['showMinutes', 'Minutes'], ['showSeconds', 'Seconds']]" :key="key"
          class="flex items-center gap-2 cursor-pointer">
          <input type="checkbox" :checked="block.data[key] !== false" class="rounded"
            @change="emit('update', { id: block.id, data: { [key]: $event.target.checked } })" />
          <span class="text-xs text-muted-foreground">{{ lbl }}</span>
        </label>
      </div>
    </div>
  </div>

  <!-- Style tab -->
  <div v-show="!tab || tab === 'style'" class="space-y-3">
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Unit style</label>
      <div class="flex rounded-md border overflow-hidden text-xs">
        <button v-for="[val, lbl] in [['box', 'Box'], ['minimal', 'Minimal']]" :key="val"
          type="button"
          class="flex-1 py-1.5 transition-colors"
          :class="(block.data.style ?? 'box') === val ? 'bg-primary text-primary-foreground' : 'bg-background text-foreground'"
          @click="emit('update', { id: block.id, data: { style: val } })">
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
          :class="(block.data.align ?? 'center') === al ? 'bg-primary text-primary-foreground' : 'bg-background text-foreground'"
          @click="emit('update', { id: block.id, data: { align: al } })">
          {{ al }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({ block: { type: Object, required: true }, tab: { type: String, default: null } })
const emit = defineEmits(['update'])
</script>
