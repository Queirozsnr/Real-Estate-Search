<script setup lang="ts">
import { computed, ref } from 'vue'

const props = defineProps<{
  images: string[]
  title: string
}>()

const SIDE_TILES = 2

const activeIndex = ref(0)
const activeImage = computed(() => props.images[activeIndex.value])

/** On desktop, the other photos are shown as a mosaic next to the main one. */
const sideTiles = computed(() => props.images
  .map((image, index) => ({ image, index }))
  .filter(tile => tile.index !== activeIndex.value)
  .slice(0, SIDE_TILES))

const hiddenCount = computed(() => Math.max(0, props.images.length - 1 - SIDE_TILES))

function show(index: number) {
  activeIndex.value = (index + props.images.length) % props.images.length
}
</script>

<template>
  <div class="space-y-3">
    <div
      class="grid gap-2 md:h-[26rem]"
      :class="sideTiles.length ? 'md:grid-cols-3 md:grid-rows-2' : ''"
    >
      <div
        class="relative aspect-[16/10] overflow-hidden rounded-xl bg-elevated md:aspect-auto"
        :class="sideTiles.length ? 'md:col-span-2 md:row-span-2' : ''"
      >
        <PropertyImage
          :src="activeImage"
          :alt="`${title}, photo ${activeIndex + 1} of ${images.length}`"
          eager
        />

        <template v-if="images.length > 1">
          <UButton
            icon="i-lucide-chevron-left"
            color="neutral"
            variant="solid"
            aria-label="Previous photo"
            class="absolute left-3 top-1/2 -translate-y-1/2 rounded-full opacity-90"
            @click="show(activeIndex - 1)"
          />
          <UButton
            icon="i-lucide-chevron-right"
            color="neutral"
            variant="solid"
            aria-label="Next photo"
            class="absolute right-3 top-1/2 -translate-y-1/2 rounded-full opacity-90"
            @click="show(activeIndex + 1)"
          />
          <span class="absolute bottom-3 right-3 rounded-md bg-black/60 px-2 py-1 text-xs text-white">
            {{ activeIndex + 1 }} / {{ images.length }}
          </span>
        </template>
      </div>

      <button
        v-for="(tile, position) in sideTiles"
        :key="tile.image"
        type="button"
        class="relative hidden overflow-hidden rounded-xl bg-elevated transition hover:opacity-90 md:block"
        :class="sideTiles.length === 1 ? 'md:row-span-2' : ''"
        :aria-label="`Show photo ${tile.index + 1}`"
        @click="show(tile.index)"
      >
        <PropertyImage
          :src="tile.image"
          alt=""
        />
        <span
          v-if="hiddenCount > 0 && position === sideTiles.length - 1"
          class="absolute inset-0 flex items-center justify-center bg-black/50 text-lg font-semibold text-white"
        >
          +{{ hiddenCount }}
        </span>
      </button>
    </div>

    <!-- Mobile: thumbnails instead of the mosaic -->
    <ul
      v-if="images.length > 1"
      class="flex gap-2 md:hidden"
    >
      <li
        v-for="(image, index) in images"
        :key="image"
      >
        <button
          type="button"
          class="h-16 w-24 overflow-hidden rounded-lg border-2 transition"
          :class="index === activeIndex ? 'border-primary' : 'border-transparent opacity-70 hover:opacity-100'"
          :aria-label="`Show photo ${index + 1}`"
          :aria-current="index === activeIndex"
          @click="show(index)"
        >
          <PropertyImage
            :src="image"
            alt=""
          />
        </button>
      </li>
    </ul>
  </div>
</template>
