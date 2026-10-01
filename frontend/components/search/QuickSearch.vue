<script setup lang="ts">
import { ref, watch } from 'vue'
import type { QuickSearchFilters } from '~/utils/quick-search'
import type { SearchFilters } from '~/types/property'

const props = withDefaults(defineProps<{
  filters: SearchFilters
  cities?: string[]
}>(), {
  cities: () => [],
})

const emit = defineEmits<{
  search: [filters: QuickSearchFilters]
}>()

const query = ref('')
const notUnderstood = ref(false)
let lastApplied: string | undefined

function submit() {
  const parsed = parseQuickSearch(query.value, props.cities)
  notUnderstood.value = query.value.trim() !== '' && Object.keys(parsed).length === 0
  if (notUnderstood.value) {
    return
  }

  lastApplied = JSON.stringify(toSearchQuery({ ...parsed, sort: DEFAULT_SORT, page: 1 }))
  emit('search', parsed)
}

// Once the filters are changed by other means, the text no longer describes the search.
watch(() => props.filters, (filters) => {
  if (lastApplied !== undefined && JSON.stringify(toSearchQuery({ ...filters, sort: DEFAULT_SORT, page: 1 })) !== lastApplied) {
    query.value = ''
    lastApplied = undefined
  }
})
</script>

<template>
  <form
    role="search"
    aria-label="Quick search"
    @submit.prevent="submit"
  >
    <div class="flex gap-2">
      <UInput
        v-model="query"
        icon="i-lucide-sparkles"
        size="xl"
        placeholder="Try: apartments in Berlin, 3+ bedrooms, max €500k"
        aria-label="Describe what you are looking for"
        class="flex-1"
        @update:model-value="notUnderstood = false"
      />
      <UButton
        type="submit"
        label="Search"
        size="xl"
      />
    </div>
    <p
      v-if="notUnderstood"
      class="mt-2 text-sm text-warning"
      role="status"
    >
      No filters recognized. Mention a city, a property type, bedrooms or a price, e.g. "house in Hamburg under 1.5m".
    </p>
  </form>
</template>
