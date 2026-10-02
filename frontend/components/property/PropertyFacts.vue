<script setup lang="ts">
import { computed } from 'vue'
import type { PropertyDetails } from '~/types/property'

const props = defineProps<{
  property: PropertyDetails
}>()

const facts = computed(() => [
  { label: 'Bedrooms', value: props.property.bedrooms === 0 ? 'Studio' : String(props.property.bedrooms) },
  { label: 'Bathrooms', value: String(props.property.bathrooms) },
  { label: 'Living area', value: formatArea(props.property.livingArea) },
  { label: 'Built', value: props.property.yearBuilt ? String(props.property.yearBuilt) : '—' },
])
</script>

<template>
  <dl class="grid grid-cols-2 overflow-hidden rounded-xl border border-default sm:grid-cols-4">
    <div
      v-for="(fact, index) in facts"
      :key="fact.label"
      class="border-default p-4"
      :class="[
        index % 2 === 1 ? 'border-l' : '',
        index >= 2 ? 'border-t sm:border-t-0' : '',
        index === 2 ? 'sm:border-l' : '',
      ]"
    >
      <dt class="text-xs text-muted">
        {{ fact.label }}
      </dt>
      <dd class="mt-1 text-lg font-semibold text-highlighted">
        {{ fact.value }}
      </dd>
    </div>
  </dl>
</template>
