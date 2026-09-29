<script setup lang="ts">
import { onMounted, ref, useTemplateRef, watch } from 'vue'

const props = withDefaults(defineProps<{
  src: string | null | undefined
  alt: string
  eager?: boolean
}>(), {
  eager: false,
})

const failed = ref(false)
const image = useTemplateRef<HTMLImageElement>('image')

watch(() => props.src, () => {
  failed.value = false
})

onMounted(() => {
  // A server-rendered <img> may already have failed before hydration attached the @error listener.
  if (image.value?.complete && image.value.naturalWidth === 0) {
    failed.value = true
  }
})
</script>

<template>
  <img
    v-if="src && !failed"
    ref="image"
    :src="src"
    :alt="alt"
    :loading="eager ? 'eager' : 'lazy'"
    decoding="async"
    class="size-full object-cover"
    @error="failed = true"
  >
  <div
    v-else
    class="flex size-full items-center justify-center bg-elevated text-dimmed"
    role="img"
    :aria-label="`${alt} (image unavailable)`"
  >
    <UIcon
      name="i-lucide-image-off"
      class="size-8"
    />
  </div>
</template>
