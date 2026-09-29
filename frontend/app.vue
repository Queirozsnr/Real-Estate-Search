<template>
  <UApp>
    <NuxtLoadingIndicator color="var(--ui-primary)" />
    <div class="min-h-screen flex flex-col bg-(--ui-bg)">
      <AppHeader />
      <main class="flex-1">
        <NuxtPage />
      </main>
      <AppFooter />
    </div>
  </UApp>
</template>
