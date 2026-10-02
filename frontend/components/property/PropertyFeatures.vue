<script setup lang="ts">
defineProps<{
  features: string[]
}>()

// Icons are listed statically so the icon bundler picks them up at build time.
const FEATURE_ICONS: Record<string, string> = {
  'balcony': 'i-lucide-fence',
  'terrace': 'i-lucide-sun',
  'garden': 'i-lucide-trees',
  'elevator': 'i-lucide-arrow-up-down',
  'parking': 'i-lucide-car',
  'garage': 'i-lucide-warehouse',
  'cellar': 'i-lucide-archive',
  'fitted kitchen': 'i-lucide-cooking-pot',
  'renovated': 'i-lucide-paintbrush',
  'fireplace': 'i-lucide-flame',
  'pool': 'i-lucide-waves',
  'water view': 'i-lucide-waves',
  'sauna': 'i-lucide-thermometer',
  'smart home': 'i-lucide-cpu',
  'concierge': 'i-lucide-concierge-bell',
  'underfloor heating': 'i-lucide-heater',
  'heat pump': 'i-lucide-heater',
  'furnished': 'i-lucide-sofa',
}
</script>

<template>
  <ul class="grid gap-x-6 gap-y-3 sm:grid-cols-2">
    <li
      v-for="feature in features"
      :key="feature"
      class="flex items-center gap-3 text-default"
    >
      <UIcon
        :name="FEATURE_ICONS[feature] ?? 'i-lucide-check'"
        class="size-5 shrink-0 text-muted"
      />
      {{ capitalize(feature) }}
    </li>
  </ul>
</template>
