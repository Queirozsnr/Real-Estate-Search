<script setup lang="ts">
import { computed } from 'vue'

// Nuxt UI 3 ships the color mode module but not a toggle component (UColorModeButton is v4 / Pro).
const colorMode = useColorMode()

const isDark = computed({
  get: () => colorMode.value === 'dark',
  set: (dark: boolean) => {
    colorMode.preference = dark ? 'dark' : 'light'
  },
})
</script>

<template>
  <!-- The resolved mode is only known in the browser (it may follow the OS preference). -->
  <ClientOnly>
    <UButton
      :icon="isDark ? 'i-lucide-moon' : 'i-lucide-sun'"
      color="neutral"
      variant="ghost"
      :aria-label="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
      @click="() => { isDark = !isDark }"
    />
    <template #fallback>
      <div class="size-8" />
    </template>
  </ClientOnly>
</template>
