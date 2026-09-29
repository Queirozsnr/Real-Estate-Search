<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
  error: unknown
}>()

const emit = defineEmits<{
  retry: []
}>()

const description = computed(() => describeApiError(props.error))
</script>

<template>
  <div
    role="alert"
    class="flex flex-col items-center rounded-xl border border-error/30 bg-error/5 px-6 py-16 text-center"
  >
    <span class="mb-4 flex size-12 items-center justify-center rounded-full bg-error/10">
      <UIcon
        name="i-lucide-triangle-alert"
        class="size-6 text-error"
      />
    </span>
    <h2 class="text-lg font-semibold text-highlighted">
      {{ description.title }}
    </h2>
    <p class="mt-1 max-w-md text-muted">
      {{ description.message }}
    </p>
    <ul
      v-if="description.details.length"
      class="mt-3 space-y-1 text-sm text-muted"
    >
      <li
        v-for="detail in description.details"
        :key="detail"
      >
        {{ detail }}
      </li>
    </ul>
    <div class="mt-6 flex flex-wrap justify-center gap-2">
      <UButton
        icon="i-lucide-refresh-cw"
        label="Try again"
        @click="emit('retry')"
      />
      <slot name="actions" />
    </div>
  </div>
</template>
