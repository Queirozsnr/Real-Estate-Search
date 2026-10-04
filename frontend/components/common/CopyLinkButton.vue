<script setup lang="ts">
import { useClipboard } from '@vueuse/core'

const { copy, copied } = useClipboard({ copiedDuring: 2000, legacy: true })
const toast = useToast()

async function copyLink() {
  try {
    await copy(window.location.href)
  }
  catch {
    toast.add({ title: 'Could not copy the link', description: 'Copy it from the address bar instead.', color: 'error' })
  }
}
</script>

<template>
  <UButton
    :label="copied ? 'Link copied' : 'Copy link'"
    :icon="copied ? 'i-lucide-check' : 'i-lucide-link'"
    block
    aria-live="polite"
    @click="copyLink"
  />
</template>
