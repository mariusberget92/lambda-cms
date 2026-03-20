<!-- resources/js/Components/BlockEditor/blocks/ComponentSettings.vue -->
<template>
  <div class="space-y-4">
    <!-- Sub-type selector -->
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Component type</label>
      <select
        :value="block.data.component"
        class="w-full rounded border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @change="update('component', $event.target.value)"
      >
        <option value="post-list">Post List</option>
      </select>
    </div>

    <template v-if="block.data.component === 'post-list'">
      <!-- Order -->
      <div>
        <label class="text-xs font-medium text-muted-foreground block mb-1">Order</label>
        <select
          :value="block.data.order"
          class="w-full rounded border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
          @change="update('order', $event.target.value)"
        >
          <option value="latest">Latest first</option>
          <option value="oldest">Oldest first</option>
          <option value="alpha">Alphabetical</option>
        </select>
      </div>

      <!-- Limit + Offset -->
      <div class="grid grid-cols-2 gap-2">
        <div>
          <label class="text-xs font-medium text-muted-foreground block mb-1">Limit</label>
          <input
            type="number"
            min="1"
            max="100"
            :value="block.data.limit"
            class="w-full rounded border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
            @input="update('limit', parseInt($event.target.value) || 6)"
          />
        </div>
        <div>
          <label class="text-xs font-medium text-muted-foreground block mb-1">Offset</label>
          <input
            type="number"
            min="0"
            :value="block.data.offset"
            class="w-full rounded border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
            @input="update('offset', parseInt($event.target.value) || 0)"
          />
        </div>
      </div>

      <!-- Featured only -->
      <label class="flex items-center gap-2 cursor-pointer">
        <input
          type="checkbox"
          :checked="block.data.featured_only"
          class="accent-primary"
          @change="update('featured_only', $event.target.checked)"
        />
        <span class="text-xs font-medium text-muted-foreground">Featured posts only</span>
      </label>

      <!-- Filter by categories -->
      <div v-if="meta.categories?.length">
        <label class="text-xs font-medium text-muted-foreground block mb-1">Filter by categories</label>
        <div class="space-y-1 max-h-32 overflow-y-auto">
          <label
            v-for="cat in meta.categories"
            :key="cat.id"
            class="flex items-center gap-2 cursor-pointer"
          >
            <input
              type="checkbox"
              :value="cat.id"
              :checked="block.data.category_ids?.includes(cat.id)"
              class="accent-primary"
              @change="toggleId('category_ids', cat.id, $event.target.checked)"
            />
            <span class="text-xs">{{ cat.name }}</span>
          </label>
        </div>
      </div>

      <!-- Filter by tags -->
      <div v-if="meta.tags?.length">
        <label class="text-xs font-medium text-muted-foreground block mb-1">Filter by tags</label>
        <div class="space-y-1 max-h-32 overflow-y-auto">
          <label
            v-for="tag in meta.tags"
            :key="tag.id"
            class="flex items-center gap-2 cursor-pointer"
          >
            <input
              type="checkbox"
              :value="tag.id"
              :checked="block.data.tag_ids?.includes(tag.id)"
              class="accent-primary"
              @change="toggleId('tag_ids', tag.id, $event.target.checked)"
            />
            <span class="text-xs">{{ tag.name }}</span>
          </label>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
const props = defineProps({
  block: { type: Object, required: true },
  meta:  { type: Object, default: () => ({ categories: [], tags: [] }) },
})

const emit = defineEmits(['update'])

function update(key, value) {
  emit('update', { id: props.block.id, data: { ...props.block.data, [key]: value } })
}

function toggleId(field, id, checked) {
  const current = [...(props.block.data[field] ?? [])]
  const next = checked ? [...current, id] : current.filter(v => v !== id)
  update(field, next)
}
</script>
