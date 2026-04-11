<!-- resources/js/Components/BlockEditor/blocks/ComponentSettings.vue -->
<template>
  <div class="space-y-4">
    <!-- Content fields -->
    <div v-show="!tab || tab === 'content'" class="space-y-3">
      <!-- Sub-type selector -->
      <div>
        <label class="text-xs font-medium text-muted-foreground block mb-1">Component type</label>
        <SelectBox size="sm"
          :model-value="block.data.component"
          :data="[{ value: 'post-list', label: 'Post List' }]"
          @update:model-value="v => update('component', v)"
        />
      </div>

      <template v-if="block.data.component === 'post-list'">
        <!-- Order -->
        <div>
          <label class="text-xs font-medium text-muted-foreground block mb-1">Order</label>
          <SelectBox size="sm"
            :model-value="block.data.order"
            :data="[
              { value: 'latest', label: 'Latest first' },
              { value: 'oldest', label: 'Oldest first' },
              { value: 'alpha',  label: 'Alphabetical' },
            ]"
            @update:model-value="v => update('order', v)"
          />
        </div>

        <!-- Limit + Offset -->
        <div class="grid grid-cols-2 gap-2">
          <div>
            <label class="text-xs font-medium text-muted-foreground block mb-1">Limit</label>
            <NumberInput size="sm"
              :model-value="block.data.limit"
              :min="1"
              :max="100"
              @update:model-value="update('limit', $event || 6)"
            />
          </div>
          <div>
            <label class="text-xs font-medium text-muted-foreground block mb-1">Offset</label>
            <NumberInput size="sm"
              :model-value="block.data.offset"
              :min="0"
              @update:model-value="update('offset', $event || 0)"
            />
          </div>
        </div>

        <!-- Featured only -->
        <label class="flex items-center gap-2 cursor-pointer">
          <input
            type="checkbox"
            :checked="block.data.featured_only"
            class="accent-nord-green"
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
                class="accent-nord-green"
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
                class="accent-nord-green"
                @change="toggleId('tag_ids', tag.id, $event.target.checked)"
              />
              <span class="text-xs">{{ tag.name }}</span>
            </label>
          </div>
        </div>
      </template>
    </div>
  </div>
</template>

<script setup>
import SelectBox from '@/Components/SelectBox.vue'
import NumberInput from '@/Components/NumberInput.vue'

const props = defineProps({
  block: { type: Object, required: true },
  meta:  { type: Object, default: () => ({ categories: [], tags: [] }) },
  tab:   { type: String, default: null },  // 'content' | 'style' | null (show all)
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
