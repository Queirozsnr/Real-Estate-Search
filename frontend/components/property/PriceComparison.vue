<script setup lang="ts">
import { computed } from 'vue'
import type { MarketComparison } from '~/types/property'

const props = defineProps<{
  market: MarketComparison
}>()

// Differences this small are noise for a handful of listings: call them "in line".
const THRESHOLD_PERCENT = 3

const comparison = computed(() => {
  const { differencePercent: diff, city } = props.market

  if (diff <= -THRESHOLD_PERCENT) {
    return { icon: 'i-lucide-trending-down', tone: 'text-success', text: `${Math.abs(diff)}% below the ${city} average` }
  }
  if (diff >= THRESHOLD_PERCENT) {
    return { icon: 'i-lucide-trending-up', tone: 'text-warning', text: `${diff}% above the ${city} average` }
  }
  return { icon: 'i-lucide-equal', tone: 'text-muted', text: `In line with the ${city} average` }
})
</script>

<template>
  <div class="rounded-lg bg-elevated/60 px-3 py-2 text-sm">
    <p
      class="flex items-center gap-1.5 font-medium"
      :class="comparison.tone"
    >
      <UIcon
        :name="comparison.icon"
        class="size-4 shrink-0"
      />
      {{ comparison.text }}
    </p>
    <p class="mt-0.5 text-xs text-muted">
      Average {{ formatPrice(market.averagePricePerSquareMetre) }}/m² across {{ market.listings }} listings
    </p>
  </div>
</template>
