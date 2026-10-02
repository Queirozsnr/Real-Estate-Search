<template>
  <header class="sticky top-0 z-40 border-b border-default bg-default/80 backdrop-blur">
    <UContainer class="flex h-16 items-center justify-between gap-4">
      <NuxtLink
        to="/"
        class="flex items-center gap-2 font-semibold text-highlighted"
      >
        <span class="flex size-8 items-center justify-center rounded-lg bg-primary text-inverted">
          <UIcon
            name="i-lucide-house"
            class="size-5"
          />
        </span>
        <span>Real Estate Search</span>
      </NuxtLink>

      <ColorModeToggle />
    </UContainer>
  </header>
</template>
