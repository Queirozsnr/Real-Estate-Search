<script setup lang="ts">
import { computed } from 'vue'
import { useClipboard } from '@vueuse/core'
import type { SearchFilters } from '~/types/property'

const props = defineProps<{
  filters: SearchFilters
}>()

const call = computed(() => formatSearchPropertiesCall(props.filters))
const { copy, copied, isSupported } = useClipboard({ copiedDuring: 1500 })
</script>

<template>
  <div
    class="group flex min-w-0 items-center gap-1 font-mono text-xs text-muted"
    title="The same search as an MCP tool call"
  >
    <code class="min-w-0 truncate">{{ call }}</code>
    <UButton
      v-if="isSupported"
      :icon="copied ? 'i-lucide-check' : 'i-lucide-copy'"
      :aria-label="copied ? 'Copied' : 'Copy MCP call'"
      color="neutral"
      variant="link"
      size="xs"
      class="shrink-0 opacity-60 group-hover:opacity-100"
      @click="copy(call)"
    />
  </div>
</template>
