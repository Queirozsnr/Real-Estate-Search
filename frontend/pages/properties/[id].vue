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
      color="primary"
      variant="link"
      class="mb-6 px-0"
      @click="backToResults"
    />

    <ErrorState
      v-if="error"
      :error="error"
      @retry="refresh"
    />

    <div
      v-else-if="status === 'pending' && !property"
      class="space-y-6"
      aria-busy="true"
    >
      <USkeleton class="h-72 w-full rounded-xl md:h-[26rem]" />
      <USkeleton class="h-6 w-24" />
      <USkeleton class="h-9 w-2/3" />
    </div>

    <article
      v-else-if="property"
      class="space-y-10"
    >
      <PropertyGallery
        :images="property.images"
        :title="property.title"
      />

      <div class="grid gap-10 lg:grid-cols-[1fr_22rem]">
        <div class="min-w-0 space-y-8">
          <header class="space-y-3">
            <UBadge
              :label="propertyTypeLabel(property.type)"
              color="neutral"
              variant="solid"
            />
            <h1 class="text-2xl font-bold tracking-tight text-highlighted sm:text-4xl">
              {{ property.title }}
            </h1>
            <p class="text-muted">
              {{ property.district }}, {{ property.city }}
            </p>
          </header>

          <PropertyFacts :property="property" />

          <USeparator />

          <section>
            <h2 class="mb-3 text-xl font-semibold text-highlighted">
              About this property
            </h2>
            <p class="max-w-prose leading-relaxed text-default">
              {{ property.description }}
            </p>
          </section>

          <template v-if="property.features.length">
            <USeparator />

            <section>
              <h2 class="mb-4 text-xl font-semibold text-highlighted">
                What this place offers
              </h2>
              <PropertyFeatures :features="property.features" />
            </section>
          </template>
        </div>

        <aside>
          <div class="sticky top-24 space-y-4 rounded-xl border border-default bg-elevated/40 p-6">
            <div>
              <p class="text-sm text-muted">
                Purchase price
              </p>
              <p class="text-3xl font-bold text-highlighted">
                {{ formatPrice(property.price) }}
              </p>
              <p class="text-sm text-muted">
                {{ formatPrice(property.pricePerSquareMetre) }}/m²
              </p>
            </div>

            <PriceComparison :market="property.market" />

            <UButton
              :to="`mailto:agent@example.com?subject=${encodeURIComponent(`Enquiry: ${property.title}`)}`"
              label="Contact agent"
              block
              size="xl"
            />

            <p class="text-center text-xs text-muted">
              Listed on {{ formatDate(property.listedAt) }}
            </p>
          </div>
        </aside>
      </div>

      <USeparator />

      <section>
        <h2 class="mb-1 text-xl font-semibold text-highlighted">
          Location
        </h2>
        <p class="mb-4 flex items-center gap-1.5 text-muted">
          <UIcon
            name="i-lucide-map-pin"
            class="size-4 shrink-0"
          />
          {{ property.address }}
        </p>
        <PropertyLocationMap
          :latitude="property.location.latitude"
          :longitude="property.location.longitude"
          :label="property.address"
        />
      </section>
    </article>
  </UContainer>
</template>
