<template>
  <div
    class="flex flex-col overflow-hidden rounded-xl border border-default"
    aria-hidden="true"
  >
    <USkeleton class="aspect-[4/3] rounded-none" />
    <div class="space-y-3 p-4">
      <USkeleton class="h-6 w-1/3" />
      <USkeleton class="h-4 w-4/5" />
      <USkeleton class="h-4 w-1/2" />
      <USkeleton class="mt-4 h-4 w-full" />
    </div>
  </div>
</template>
