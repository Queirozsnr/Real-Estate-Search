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
  <div class="flex min-w-0 items-center gap-2 text-xs text-muted">
    <span class="shrink-0">Same search via MCP:</span>
    <code
      class="min-w-0 truncate rounded bg-elevated px-2 py-1 font-mono text-default"
      :title="call"
    >{{ call }}</code>
    <UButton
      v-if="isSupported"
      :icon="copied ? 'i-lucide-check' : 'i-lucide-copy'"
      :aria-label="copied ? 'Copied' : 'Copy MCP call'"
      color="neutral"
      variant="ghost"
      size="xs"
      @click="copy(call)"
    />
  </div>
</template>
