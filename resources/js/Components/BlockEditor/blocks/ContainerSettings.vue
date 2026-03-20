<!-- resources/js/components/BlockEditor/blocks/ContainerSettings.vue -->
<template>
  <div class="space-y-3">

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Direction</label>
      <select :value="block.data.direction" @change="update('direction', $event.target.value)"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-xs">
        <option value="row">Row (horizontal)</option>
        <option value="column">Column (vertical)</option>
      </select>
    </div>

    <div class="flex items-center gap-2">
      <input type="checkbox" :checked="block.data.wrap" @change="update('wrap', $event.target.checked)"
        id="container-wrap" class="rounded border-border" />
      <label for="container-wrap" class="text-xs font-medium text-muted-foreground">Wrap items</label>
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Gap: {{ block.data.gap }}</label>
      <input type="range" min="0" max="16" :value="block.data.gap"
        @input="update('gap', parseInt($event.target.value))"
        class="w-full" />
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Justify content</label>
      <select :value="block.data.justify" @change="update('justify', $event.target.value)"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-xs">
        <option value="start">Start</option>
        <option value="center">Center</option>
        <option value="end">End</option>
        <option value="between">Space between</option>
        <option value="around">Space around</option>
      </select>
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Align items</label>
      <select :value="block.data.align" @change="update('align', $event.target.value)"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-xs">
        <option value="start">Start</option>
        <option value="center">Center</option>
        <option value="end">End</option>
        <option value="stretch">Stretch</option>
      </select>
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Max width</label>
      <select :value="block.data.maxWidth" @change="update('maxWidth', $event.target.value)"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-xs">
        <option value="full">Full</option>
        <option value="prose">Prose (65ch)</option>
        <option value="sm">SM (24rem)</option>
        <option value="md">MD (28rem)</option>
        <option value="lg">LG (32rem)</option>
        <option value="xl">XL (36rem)</option>
        <option value="2xl">2XL (42rem)</option>
      </select>
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Padding: {{ block.data.padding }}</label>
      <input type="range" min="0" max="16" :value="block.data.padding"
        @input="update('padding', parseInt($event.target.value))"
        class="w-full" />
    </div>

  </div>
</template>

<script setup>
const props = defineProps({ block: { type: Object, required: true } })
const emit = defineEmits(['update'])

function update(key, value) {
  emit('update', { id: props.block.id, data: { [key]: value } })
}
</script>
