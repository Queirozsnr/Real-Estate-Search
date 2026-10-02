<script setup lang="ts">
import { computed, ref } from 'vue'
import type { PropertySort } from '~/types/property'
import type { QuickSearchFilters } from '~/utils/quick-search'

useSeoMeta({ title: 'Find your next home' })

const { filters, data, error, refresh, isLoading, applyFilters, goToPage, resetFilters } = usePropertySearch()
const { data: facets, refresh: refreshFacets } = useSearchFacets()

function retry() {
  refresh()
  if (!facets.value) {
    refreshFacets()
  }
}

const activeFilterCount = computed(() => countActiveFilters(filters.value))
const mobileFiltersOpen = ref(false)
const cityNames = computed(() => facets.value?.cities.map(city => city.name) ?? [])

/** The text describes the whole search, so filters it does not mention are cleared (sort is kept). */
function applyQuickSearch(parsed: QuickSearchFilters) {
  applyFilters({
    city: parsed.city,
    minPrice: parsed.minPrice,
    maxPrice: parsed.maxPrice,
    minBedrooms: parsed.minBedrooms,
    type: parsed.type,
  })
}

const sort = computed<PropertySort>({
  get: () => filters.value.sort,
  set: value => applyFilters({ sort: value }),
})

/** Distinguishes "nothing matches" from "this page no longer exists" (e.g. an old link). */
const isPastLastPage = computed(() => !!data.value && data.value.meta.total > 0 && data.value.data.length === 0)
</script>

<template>
  <UContainer class="py-8">
    <div class="mb-6">
      <h1 class="text-3xl font-bold tracking-tight text-highlighted">
        Find your next home
      </h1>
      <p class="mt-2 text-muted">
        Browse apartments, houses and more in Germany's largest cities.
      </p>
    </div>

    <QuickSearch
      :filters="filters"
      :cities="cityNames"
      class="mb-8"
      @search="applyQuickSearch"
    />

    <div class="grid gap-8 lg:grid-cols-[17rem_1fr]">
      <!-- Desktop: persistent sidebar -->
      <aside class="hidden lg:block">
        <div class="sticky top-24 rounded-xl border border-default bg-elevated/40 p-5">
          <SearchFilters
            :filters="filters"
            :facets="facets"
            @apply="applyFilters"
            @reset="resetFilters"
          />
        </div>
      </aside>

      <section
        aria-label="Search results"
        :aria-busy="isLoading"
      >
        <SearchResultsHeader
          v-model:sort="sort"
          :total="data?.meta.total"
          :loading="isLoading"
        >
          <!-- Mobile: filters in a slide-over -->
          <USlideover
            v-model:open="mobileFiltersOpen"
            title="Filters"
            side="left"
            class="lg:hidden"
          >
            <UButton
              icon="i-lucide-sliders-horizontal"
              :label="activeFilterCount ? `Filters (${activeFilterCount})` : 'Filters'"
              color="neutral"
              variant="outline"
              class="lg:hidden"
            />
            <template #body>
              <SearchFilters
                :filters="filters"
                :facets="facets"
                :show-title="false"
                @apply="applyFilters"
                @reset="resetFilters"
              />
            </template>
            <template #footer>
              <UButton
                :label="data ? `Show ${data.meta.total} ${data.meta.total === 1 ? 'result' : 'results'}` : 'Show results'"
                :loading="isLoading"
                block
                @click="() => { mobileFiltersOpen = false }"
              />
            </template>
          </USlideover>
        </SearchResultsHeader>

        <ErrorState
          v-if="error"
          :error="error"
          @retry="retry"
        >
          <template
            v-if="activeFilterCount"
            #actions
          >
            <UButton
              label="Clear filters"
              color="neutral"
              variant="outline"
              @click="resetFilters"
            />
          </template>
        </ErrorState>

        <PropertyGrid
          v-else-if="!data"
          :skeleton-count="6"
        />

        <EmptyState
          v-else-if="isPastLastPage"
          title="This page does not exist"
          :description="`This search only has ${data.meta.totalPages} ${data.meta.totalPages === 1 ? 'page' : 'pages'} of results.`"
          icon="i-lucide-file-question"
        >
          <UButton
            label="Go to first page"
            @click="goToPage(1)"
          />
        </EmptyState>

        <EmptyState
          v-else-if="data.data.length === 0"
          title="No properties match your search"
          description="Try a different city, widen the price range or lower the number of bedrooms."
        >
          <UButton
            label="Clear all filters"
            icon="i-lucide-x"
            @click="resetFilters"
          />
        </EmptyState>

        <template v-else>
          <PropertyGrid
            :properties="data.data"
            class="transition-opacity"
            :class="{ 'pointer-events-none opacity-50': isLoading }"
          />

          <div
            v-if="data.meta.totalPages > 1"
            class="mt-8 flex justify-center"
          >
            <UPagination
              :page="filters.page"
              :total="data.meta.total"
              :items-per-page="data.meta.perPage"
              @update:page="goToPage"
            />
          </div>
        </template>
      </section>
    </div>
  </UContainer>
</template>
