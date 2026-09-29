<script setup lang="ts">
import { PROPERTY_SORTS } from '~/types/property'
import type { PropertySort } from '~/types/property'

defineProps<{
  total?: number
  loading: boolean
}>()

const sort = defineModel<PropertySort>('sort', { required: true })

const sortItems = PROPERTY_SORTS.map(value => ({ label: SORT_LABELS[value], value }))
</script>

<template>
  <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
    <p
      class="text-sm text-muted"
      aria-live="polite"
    >
      <template v-if="total === undefined">
        {{ loading ? 'Searching…' : '' }}
      </template>
      <template v-else>
        <span class="font-semibold text-highlighted">{{ total }}</span>
        {{ total === 1 ? 'property' : 'properties' }} found
        <UIcon
          v-if="loading"
          name="i-lucide-loader-circle"
          class="ml-1 size-4 animate-spin align-middle"
        />
      </template>
    </p>

    <div class="flex items-center gap-2">
      <slot />
      <USelect
        v-model="sort"
        :items="sortItems"
        icon="i-lucide-arrow-up-down"
        aria-label="Sort results"
        class="w-48"
      />
    </div>
  </div>
</template>
