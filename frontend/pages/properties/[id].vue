<script setup lang="ts">
import { computed } from 'vue'
import type { PropertyResponse } from '~/types/property'

const route = useRoute()
const router = useRouter()

const id = Number(route.params.id)
if (!Number.isInteger(id) || id < 1) {
  throw createError({ statusCode: 404, statusMessage: 'Property not found', fatal: true })
}

const { data, error, refresh, status } = await useFetch<PropertyResponse>(`/api/properties/${id}`)

// A missing property is a real 404 page (correct status code for SSR, crawlers and shared links);
// other failures stay inline with a retry.
if (error.value?.statusCode === 404) {
  throw createError({ statusCode: 404, statusMessage: 'Property not found', fatal: true })
}

const property = computed(() => data.value?.data)

useSeoMeta({
  title: () => property.value?.title ?? 'Property',
  description: () => property.value?.description,
  ogImage: () => property.value?.images[0],
})

/** Returns to the previous search (keeping its filters) when there is one. */
function backToResults() {
  if (import.meta.client && window.history.state?.back) {
    router.back()
  }
  else {
    navigateTo('/')
  }
}
</script>

<template>
  <UContainer class="py-8">
    <UButton
      label="Back to results"
      icon="i-lucide-arrow-left"
      color="neutral"
      variant="ghost"
      class="-ml-2 mb-6"
      @click="backToResults"
    />

    <ErrorState
      v-if="error"
      :error="error"
      @retry="refresh"
    />

    <div
      v-else-if="status === 'pending' && !property"
      class="space-y-4"
      aria-busy="true"
    >
      <USkeleton class="h-8 w-2/3" />
      <USkeleton class="aspect-[16/10] w-full lg:w-2/3" />
    </div>

    <article
      v-else-if="property"
      class="grid gap-10 lg:grid-cols-[1fr_22rem]"
    >
      <div class="min-w-0 space-y-8">
        <header>
          <div class="mb-2 flex flex-wrap items-center gap-2">
            <UBadge
              :label="propertyTypeLabel(property.type)"
              variant="subtle"
            />
            <span class="flex items-center gap-1 text-sm text-muted">
              <UIcon
                name="i-lucide-map-pin"
                class="size-4"
              />
              {{ property.district }}, {{ property.city }}
            </span>
          </div>
          <h1 class="text-2xl font-bold tracking-tight text-highlighted sm:text-3xl">
            {{ property.title }}
          </h1>
        </header>

        <PropertyGallery
          :images="property.images"
          :title="property.title"
        />

        <section>
          <h2 class="mb-3 text-lg font-semibold text-highlighted">
            Key facts
          </h2>
          <PropertyFacts :property="property" />
        </section>

        <section>
          <h2 class="mb-3 text-lg font-semibold text-highlighted">
            Description
          </h2>
          <p class="leading-relaxed text-default">
            {{ property.description }}
          </p>
        </section>

        <section v-if="property.features.length">
          <h2 class="mb-3 text-lg font-semibold text-highlighted">
            Features
          </h2>
          <ul class="flex flex-wrap gap-2">
            <li
              v-for="feature in property.features"
              :key="feature"
            >
              <UBadge
                :label="capitalize(feature)"
                icon="i-lucide-check"
                color="neutral"
                variant="outline"
                size="lg"
              />
            </li>
          </ul>
        </section>

        <section>
          <h2 class="mb-3 text-lg font-semibold text-highlighted">
            Location
          </h2>
          <PropertyLocationMap
            :latitude="property.location.latitude"
            :longitude="property.location.longitude"
            :label="property.address"
          />
        </section>
      </div>

      <aside>
        <div class="sticky top-24 space-y-4 rounded-xl border border-default p-6">
          <div>
            <p class="text-sm text-muted">
              Purchase price
            </p>
            <p class="text-3xl font-bold text-highlighted">
              {{ formatPrice(property.price) }}
            </p>
            <p class="text-sm text-muted">
              {{ formatPrice(property.pricePerSquareMetre) }} / m²
            </p>
          </div>

          <USeparator />

          <ul class="space-y-2 text-sm">
            <li class="flex items-center gap-2">
              <UIcon
                name="i-lucide-bed-double"
                class="size-4 text-muted"
              />
              {{ formatBedrooms(property.bedrooms) }}
            </li>
            <li class="flex items-center gap-2">
              <UIcon
                name="i-lucide-bath"
                class="size-4 text-muted"
              />
              {{ formatBathrooms(property.bathrooms) }}
            </li>
            <li class="flex items-center gap-2">
              <UIcon
                name="i-lucide-ruler"
                class="size-4 text-muted"
              />
              {{ formatArea(property.livingArea) }}
            </li>
            <li class="flex items-start gap-2">
              <UIcon
                name="i-lucide-map-pin"
                class="mt-0.5 size-4 shrink-0 text-muted"
              />
              {{ property.address }}
            </li>
          </ul>

          <UButton
            :to="`mailto:agent@example.com?subject=${encodeURIComponent(`Enquiry: ${property.title}`)}`"
            label="Contact agent"
            icon="i-lucide-mail"
            block
            size="lg"
          />
        </div>
      </aside>
    </article>
  </UContainer>
</template>
