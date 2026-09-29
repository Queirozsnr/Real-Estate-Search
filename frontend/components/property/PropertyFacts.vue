<script setup lang="ts">
import { computed } from 'vue'
import type { PropertyDetails } from '~/types/property'

const props = defineProps<{
  property: PropertyDetails
}>()

const facts = computed(() => [
  { icon: 'i-lucide-building-2', label: 'Type', value: propertyTypeLabel(props.property.type) },
  { icon: 'i-lucide-bed-double', label: 'Bedrooms', value: props.property.bedrooms === 0 ? 'Studio' : String(props.property.bedrooms) },
  { icon: 'i-lucide-bath', label: 'Bathrooms', value: String(props.property.bathrooms) },
  { icon: 'i-lucide-ruler', label: 'Living area', value: formatArea(props.property.livingArea) },
  { icon: 'i-lucide-calendar', label: 'Year built', value: props.property.yearBuilt ? String(props.property.yearBuilt) : 'Unknown' },
  { icon: 'i-lucide-clock', label: 'Listed on', value: formatDate(props.property.listedAt) },
])
</script>

<template>
  <dl class="grid grid-cols-2 gap-3 sm:grid-cols-3">
    <div
      v-for="fact in facts"
      :key="fact.label"
      class="rounded-lg border border-default p-3"
    >
      <dt class="flex items-center gap-1.5 text-xs text-muted">
        <UIcon
          :name="fact.icon"
          class="size-4"
        />
        {{ fact.label }}
      </dt>
      <dd class="mt-1 font-semibold text-highlighted">
        {{ fact.value }}
      </dd>
    </div>
  </dl>
</template>
