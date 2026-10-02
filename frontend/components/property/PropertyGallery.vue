<script setup lang="ts">
import { computed, ref } from 'vue'

const props = defineProps<{
  images: string[]
  title: string
}>()

const SIDE_TILES = 2

const activeIndex = ref(0)
const activeImage = computed(() => props.images[activeIndex.value])
const allPhotosOpen = ref(false)

/** On desktop, the other photos are shown as a mosaic next to the main one. */
const sideTiles = computed(() => props.images
  .map((image, index) => ({ image, index }))
  .filter(tile => tile.index !== activeIndex.value)
  .slice(0, SIDE_TILES))

const hiddenCount = computed(() => Math.max(0, props.images.length - 1 - SIDE_TILES))

function show(index: number) {
  activeIndex.value = (index + props.images.length) % props.images.length
}

function openAllPhotos() {
  allPhotosOpen.value = true
}

/** The last side tile carries the "+N" overlay and opens the full list instead. */
function isMoreTile(position: number) {
  return hiddenCount.value > 0 && position === sideTiles.value.length - 1
}

function onTileClick(position: number, index: number) {
  if (isMoreTile(position)) {
    openAllPhotos()
  }
  else {
    show(index)
  }
}

/** Picking a photo in the "all photos" view makes it the main one. */
function showFromAllPhotos(index: number) {
  show(index)
  allPhotosOpen.value = false
}
</script>

<template>
  <div class="space-y-3">
    <div class="relative">
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
            <span class="absolute bottom-3 left-3 rounded-md bg-black/60 px-2 py-1 text-xs text-white">
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
          :aria-label="isMoreTile(position) ? `Show all ${images.length} photos` : `Show photo ${tile.index + 1}`"
          @click="onTileClick(position, tile.index)"
        >
          <PropertyImage
            :src="tile.image"
            alt=""
          />
          <span
            v-if="isMoreTile(position)"
            class="absolute inset-0 flex items-center justify-center bg-black/50 text-lg font-semibold text-white"
          >
            +{{ hiddenCount }}
          </span>
        </button>
      </div>

      <UButton
        v-if="hiddenCount > 0"
        :label="`Show all ${images.length} photos`"
        icon="i-lucide-grid-3x3"
        color="neutral"
        variant="solid"
        size="sm"
        class="absolute bottom-3 right-3 shadow-md"
        @click="openAllPhotos"
      />
    </div>

    <!-- Mobile: thumbnails instead of the mosaic (scrolls horizontally when there are many) -->
    <ul
      v-if="images.length > 1"
      class="-mx-4 flex gap-2 overflow-x-auto px-4 pb-1 md:hidden"
    >
      <li
        v-for="(image, index) in images"
        :key="image"
        class="shrink-0"
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

    <UModal
      v-model:open="allPhotosOpen"
      fullscreen
      :title="title"
      :description="`${images.length} photos`"
    >
      <template #body>
        <ul class="mx-auto grid max-w-4xl gap-2 sm:grid-cols-2">
          <li
            v-for="(image, index) in images"
            :key="image"
            :class="index % 3 === 0 ? 'sm:col-span-2' : ''"
          >
            <button
              type="button"
              class="block w-full overflow-hidden rounded-lg transition hover:opacity-90"
              :class="index % 3 === 0 ? 'aspect-[16/9]' : 'aspect-[4/3]'"
              :aria-label="`Open photo ${index + 1} in the gallery`"
              @click="showFromAllPhotos(index)"
            >
              <PropertyImage
                :src="image"
                :alt="`${title}, photo ${index + 1} of ${images.length}`"
              />
            </button>
          </li>
        </ul>
      </template>
    </UModal>
  </div>
</template>
