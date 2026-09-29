<script setup lang="ts">
import type { PropertySummary } from '~/types/property'

withDefaults(defineProps<{
  properties?: PropertySummary[]
  skeletonCount?: number
}>(), {
  properties: () => [],
  skeletonCount: 0,
})
</script>

<template>
  <ul class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
    <template v-if="skeletonCount > 0">
      <li
        v-for="n in skeletonCount"
        :key="`skeleton-${n}`"
      >
        <PropertyCardSkeleton />
      </li>
    </template>
    <template v-else>
      <li
        v-for="property in properties"
        :key="property.id"
      >
        <PropertyCard :property="property" />
      </li>
    </template>
  </ul>
</template>
