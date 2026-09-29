<script setup lang="ts">
import { computed } from 'vue'
import type { NuxtError } from '#app'

const props = defineProps<{
  error: NuxtError
}>()

const isNotFound = computed(() => props.error.statusCode === 404)

useSeoMeta({ title: () => (isNotFound.value ? 'Page not found' : 'Error') })

function backToSearch() {
  clearError({ redirect: '/' })
}
</script>

<template>
  <UApp>
    <div class="min-h-screen flex flex-col bg-(--ui-bg)">
      <AppHeader />
      <UContainer class="flex flex-1 flex-col items-center justify-center py-24 text-center">
        <p class="text-6xl font-bold text-primary">
          {{ error.statusCode }}
        </p>
        <h1 class="mt-4 text-2xl font-semibold text-highlighted">
          {{ isNotFound ? 'We could not find this page' : 'Something went wrong' }}
        </h1>
        <p class="mt-2 max-w-md text-muted">
          {{ isNotFound
            ? 'The property may have been sold or removed, or the link is incorrect.'
            : 'An unexpected error occurred. Please try again later.' }}
        </p>
        <UButton
          label="Back to search"
          icon="i-lucide-search"
          size="lg"
          class="mt-8"
          @click="backToSearch"
        />
      </UContainer>
    </div>
  </UApp>
</template>
