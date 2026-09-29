<script setup lang="ts">
withDefaults(defineProps<{
  title: string
  description?: string
  icon?: string
}>(), {
  icon: 'i-lucide-search-x',
})
</script>

<template>
  <div class="flex flex-col items-center rounded-xl border border-dashed border-default px-6 py-16 text-center">
    <span class="mb-4 flex size-12 items-center justify-center rounded-full bg-elevated">
      <UIcon
        :name="icon"
        class="size-6 text-muted"
      />
    </span>
    <h2 class="text-lg font-semibold text-highlighted">
      {{ title }}
    </h2>
    <p
      v-if="description"
      class="mt-1 max-w-md text-muted"
    >
      {{ description }}
    </p>
    <div
      v-if="$slots.default"
      class="mt-6 flex flex-wrap justify-center gap-2"
    >
      <slot />
    </div>
  </div>
</template>
