<script setup lang="ts">
import type { PropertySummary } from '~/types/property'

defineProps<{
  property: PropertySummary
}>()
</script>

<template>
  <NuxtLink
    :to="`/properties/${property.id}`"
    class="group flex h-full flex-col overflow-hidden rounded-xl border border-default bg-default transition hover:-translate-y-0.5 hover:shadow-lg focus-visible:outline-2 focus-visible:outline-primary"
  >
    <div class="relative aspect-[4/3] overflow-hidden">
      <PropertyImage
        :src="property.imageUrl"
        :alt="property.title"
        class="transition duration-300 group-hover:scale-105"
      />
      <UBadge
        :label="propertyTypeLabel(property.type)"
        color="neutral"
        variant="solid"
        class="absolute left-3 top-3"
      />
    </div>

    <div class="flex flex-1 flex-col gap-2 p-4">
      <div class="flex items-baseline justify-between gap-2">
        <p class="text-xl font-bold text-highlighted">
          {{ formatPrice(property.price) }}
        </p>
        <p class="text-sm text-muted">
          {{ formatPrice(property.pricePerSquareMetre) }}/m²
        </p>
      </div>
      <h3 class="line-clamp-2 font-medium text-default">
        {{ property.title }}
      </h3>
      <p class="flex items-center gap-1 text-sm text-muted">
        <UIcon
          name="i-lucide-map-pin"
          class="size-4 shrink-0"
        />
        {{ property.district }}, {{ property.city }}
      </p>

      <ul class="mt-auto flex flex-wrap gap-x-4 gap-y-1 border-t border-default pt-3 text-sm text-muted">
        <li class="flex items-center gap-1">
          <UIcon
            name="i-lucide-bed-double"
            class="size-4"
          />
          {{ formatBedrooms(property.bedrooms) }}
        </li>
        <li class="flex items-center gap-1">
          <UIcon
            name="i-lucide-bath"
            class="size-4"
          />
          {{ property.bathrooms }}
        </li>
        <li class="flex items-center gap-1">
          <UIcon
            name="i-lucide-ruler"
            class="size-4"
          />
          {{ formatArea(property.livingArea) }}
        </li>
      </ul>
    </div>
  </NuxtLink>
</template>
