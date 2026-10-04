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
const feedback = ref<{ understood: string[], ignored: string[] } | null>(null)
let lastApplied: string | undefined

function submit() {
  const { filters, ignored } = parseQuickSearch(query.value, props.cities)
  const understood = summarizeQuickSearch(filters)
  feedback.value = query.value.trim() === '' ? null : { understood, ignored }
  if (query.value.trim() !== '' && understood.length === 0) {
    return
  }

  lastApplied = JSON.stringify(toSearchQuery({ ...filters, sort: DEFAULT_SORT, page: 1 }))
  emit('search', filters)
}

// Once the filters are changed by other means, the text no longer describes the search.
watch(() => props.filters, (filters) => {
  if (lastApplied !== undefined && JSON.stringify(toSearchQuery({ ...filters, sort: DEFAULT_SORT, page: 1 })) !== lastApplied) {
    query.value = ''
    feedback.value = null
    lastApplied = undefined
  }
})

const SUPPORTED = 'You can search by city, property type, minimum number of bedrooms and price, e.g. "house in Hamburg under 1.5m".'
</script>

<template>
  <form
    role="search"
    aria-label="Quick search"
    @submit.prevent="submit"
  >
    <div class="flex items-center gap-2 rounded-xl border border-default bg-elevated/40 py-1.5 pl-4 pr-1.5 transition-colors focus-within:border-primary">
      <UIcon
        name="i-lucide-sparkles"
        class="size-4 shrink-0 text-primary"
      />
      <UInput
        v-model="query"
        variant="none"
        size="lg"
        placeholder="Try: apartments in Berlin, 3+ bedrooms, max €500k"
        aria-label="Describe what you are looking for"
        class="min-w-0 flex-1"
        :ui="{ base: 'px-0' }"
        @update:model-value="() => { feedback = null }"
      />
      <UButton
        type="submit"
        label="Search"
        size="lg"
      />
    </div>
    <div
      v-if="feedback"
      class="mt-2 space-y-1 text-sm"
      role="status"
    >
      <p
        v-if="feedback.understood.length"
        class="text-muted"
      >
        Searching for: <span class="text-default">{{ feedback.understood.join(' · ') }}</span>
      </p>
      <p
        v-else
        class="text-warning"
      >
        No filters recognized. {{ SUPPORTED }}
      </p>
      <p
        v-if="feedback.understood.length && feedback.ignored.length"
        class="text-warning"
      >
        Ignored {{ feedback.ignored.map(part => `"${part}"`).join(', ') }}. {{ SUPPORTED }}
      </p>
    </div>
  </form>
</template>
