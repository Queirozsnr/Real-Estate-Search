<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useDebounceFn } from '@vueuse/core'
import { PROPERTY_TYPES } from '~/types/property'
import type { PropertyType, SearchFacets, SearchFilters } from '~/types/property'

const props = withDefaults(defineProps<{
  filters: SearchFilters
  facets?: SearchFacets | null
  /** Hidden when the container already shows a title (mobile slide-over). */
  showTitle?: boolean
}>(), {
  facets: null,
  showTitle: true,
})

const emit = defineEmits<{
  apply: [changes: Partial<SearchFilters>]
  reset: []
}>()

// Select items cannot have an empty value, so "no filter" is represented by a sentinel.
const ANY = 'any'
const BEDROOM_OPTIONS = [0, 1, 2, 3, 4, 5] as const
const PRICE_STEP = 25_000
const priceFormat: Intl.NumberFormatOptions = { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 }

const cityItems = computed(() => {
  const cities = (props.facets?.cities ?? []).map(city => ({ label: `${city.name} (${city.count})`, value: city.name }))
  const current = props.filters.city

  // Keep a city coming from the URL selectable even if it is not part of the dataset.
  if (current && !cities.some(city => city.value.toLowerCase() === current.toLowerCase())) {
    cities.push({ label: current, value: current })
  }

  return [{ label: 'All cities', value: ANY }, ...cities]
})

const selectedCity = computed<string>({
  get: () => {
    const current = props.filters.city?.toLowerCase()
    return cityItems.value.find(item => item.value !== ANY && item.value.toLowerCase() === current)?.value ?? ANY
  },
  set: value => emit('apply', { city: value === ANY ? undefined : value }),
})

const typeItems = computed(() => [
  { label: 'All types', value: ANY },
  ...PROPERTY_TYPES.map((type) => {
    const count = props.facets?.types.find(facet => facet.value === type)?.count
    return { label: count === undefined ? propertyTypeLabel(type) : `${propertyTypeLabel(type)} (${count})`, value: type }
  }),
])

const selectedType = computed<string>({
  get: () => props.filters.type ?? ANY,
  set: value => emit('apply', { type: value === ANY ? undefined : (value as PropertyType) }),
})

function selectBedrooms(count: number) {
  emit('apply', { minBedrooms: count === 0 ? undefined : count })
}

// Prices are debounced and validated locally, so the API is never called with an inverted range.
const minPrice = ref<number | null>(props.filters.minPrice ?? null)
const maxPrice = ref<number | null>(props.filters.maxPrice ?? null)

watch(() => [props.filters.minPrice, props.filters.maxPrice] as const, ([min, max]) => {
  minPrice.value = min ?? null
  maxPrice.value = max ?? null
})

const priceError = computed(() => {
  if (minPrice.value != null && maxPrice.value != null && minPrice.value > maxPrice.value) {
    return 'The minimum price cannot be higher than the maximum price.'
  }
  return undefined
})

const applyPrice = useDebounceFn(() => {
  if (priceError.value) {
    return
  }

  const changes = { minPrice: minPrice.value ?? undefined, maxPrice: maxPrice.value ?? undefined }
  if (changes.minPrice !== props.filters.minPrice || changes.maxPrice !== props.filters.maxPrice) {
    emit('apply', changes)
  }
}, 500)

watch([minPrice, maxPrice], applyPrice)

const activeFilterCount = computed(() => countActiveFilters(props.filters))
</script>

<template>
  <form
    role="search"
    aria-label="Property filters"
    class="space-y-6"
    @submit.prevent="applyPrice"
  >
    <div
      v-if="showTitle || activeFilterCount > 0"
      class="flex items-center justify-between"
    >
      <h2
        v-if="showTitle"
        class="font-semibold text-highlighted"
      >
        Filters
      </h2>
      <UButton
        v-if="activeFilterCount > 0"
        label="Clear all"
        icon="i-lucide-x"
        color="neutral"
        variant="link"
        size="sm"
        @click="emit('reset')"
      />
    </div>

    <UFormField
      label="City"
      name="city"
    >
      <USelect
        v-model="selectedCity"
        :items="cityItems"
        icon="i-lucide-map-pin"
        class="w-full"
      />
    </UFormField>

    <UFormField
      label="Property type"
      name="type"
    >
      <USelect
        v-model="selectedType"
        :items="typeItems"
        icon="i-lucide-building-2"
        class="w-full"
      />
    </UFormField>

    <UFormField
      label="Bedrooms"
      name="minBedrooms"
    >
      <div
        class="grid grid-cols-6 gap-1"
        role="radiogroup"
        aria-label="Minimum number of bedrooms"
      >
        <UButton
          v-for="count in BEDROOM_OPTIONS"
          :key="count"
          :label="count === 0 ? 'Any' : `${count}+`"
          :color="(filters.minBedrooms ?? 0) === count ? 'primary' : 'neutral'"
          :variant="(filters.minBedrooms ?? 0) === count ? 'solid' : 'outline'"
          role="radio"
          :aria-label="count === 0 ? 'Any number of bedrooms' : `${count} or more bedrooms`"
          :aria-checked="(filters.minBedrooms ?? 0) === count"
          size="sm"
          class="justify-center px-0"
          @click="selectBedrooms(count)"
        />
      </div>
    </UFormField>

    <UFormField
      label="Price"
      name="price"
      :error="priceError"
    >
      <div class="grid gap-2">
        <UInputNumber
          v-model="minPrice"
          :min="0"
          :step="PRICE_STEP"
          :format-options="priceFormat"
          placeholder="No min"
          aria-label="Minimum price"
        />
        <UInputNumber
          v-model="maxPrice"
          :min="0"
          :step="PRICE_STEP"
          :format-options="priceFormat"
          placeholder="No max"
          aria-label="Maximum price"
        />
      </div>
      <template
        v-if="facets"
        #help
      >
        Listings range from {{ formatPrice(facets.minPrice) }} to {{ formatPrice(facets.maxPrice) }}.
      </template>
    </UFormField>
  </form>
</template>
