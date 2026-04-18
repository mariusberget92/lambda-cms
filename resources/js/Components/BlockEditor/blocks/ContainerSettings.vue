<script setup>
import { computed } from 'vue'

const props = defineProps({
  block: { type: Object, required: true },
  tab:   { type: String, default: null },
})
const emit = defineEmits(['update'])

const mode = computed(() => props.block.data.mode ?? 'flex')

function update(key, value) {
  emit('update', { id: props.block.id, data: { [key]: value } })
}
</script>

<template>
  <div>

    <!-- Content tab fields -->
    <div v-show="!tab || tab === 'content'" class="space-y-3">

      <!-- Mode toggle -->
      <div>
        <label class="text-xs font-medium text-muted-foreground block mb-1">Mode</label>
        <div class="flex rounded-md border overflow-hidden text-xs">
          <button type="button"
            v-for="m in ['flex', 'grid', 'inline-flex']" :key="m"
            class="flex-1 py-1.5 transition-colors capitalize"
            :class="mode === m ? 'bg-primary text-primary-foreground' : 'bg-background text-foreground'"
            @click="update('mode', m)">
            {{ m }}
          </button>
        </div>
      </div>

    </div>

  </div>
</template>
