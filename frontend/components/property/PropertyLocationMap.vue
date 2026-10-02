<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
  latitude: number
  longitude: number
  label: string
}>()

// OpenStreetMap embed: a real map without adding a mapping library or an API key.
const DELTA = 0.01
const embedUrl = computed(() => {
  const { latitude: lat, longitude: lon } = props
  const bbox = [lon - DELTA, lat - DELTA / 2, lon + DELTA, lat + DELTA / 2].join(',')
  return `https://www.openstreetmap.org/export/embed.html?bbox=${bbox}&layer=mapnik&marker=${lat},${lon}`
})
const fullMapUrl = computed(() => `https://www.openstreetmap.org/?mlat=${props.latitude}&mlon=${props.longitude}#map=15/${props.latitude}/${props.longitude}`)
</script>

<template>
  <div class="overflow-hidden rounded-xl border border-default">
    <iframe
      :src="embedUrl"
      :title="`Map showing ${label}`"
      class="h-80 w-full md:h-[26rem]"
      loading="lazy"
      referrerpolicy="no-referrer"
    />
    <div class="flex items-center justify-between gap-2 px-4 py-2 text-sm">
      <span class="text-muted">Approximate location</span>
      <ULink
        :to="fullMapUrl"
        target="_blank"
        class="inline-flex items-center gap-1"
      >
        Open larger map
        <UIcon
          name="i-lucide-external-link"
          class="size-3.5"
        />
      </ULink>
    </div>
  </div>
</template>
