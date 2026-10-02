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

const BEDROOM_OPTIONS = [0, 1, 2, 3, 4, 5] as const
const PRICE_STEP = 25_000
const priceFormat: Intl.NumberFormatOptions = { maximumFractionDigits: 0 }

// Typing suggests the cities of the dataset; any other text can still be searched.
const cityNames = computed(() => props.facets?.cities.map(city => city.name) ?? [])
const cityCounts = computed(() => new Map(props.facets?.cities.map(city => [city.name, city.count]) ?? []))

const selectedCity = computed<string | undefined>({
  get: () => {
    const current = props.filters.city
    return cityNames.value.find(name => name.toLowerCase() === current?.toLowerCase()) ?? current
  },
  set: value => emit('apply', { city: value || undefined }),
})

function searchTypedCity(term: string) {
  const city = term.trim()
  if (city) {
    emit('apply', { city })
  }
}

const typeOptions = computed(() => [
  { label: 'All', value: undefined, count: undefined },
  ...PROPERTY_TYPES.map(type => ({
    label: propertyTypeLabel(type),
    value: type,
    count: props.facets?.types.find(facet => facet.value === type)?.count,
  })),
])

function selectType(type: PropertyType | undefined) {
  emit('apply', { type })
}

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

const priceInputUi = { increment: 'hidden', decrement: 'hidden', base: 'px-2.5 text-left' }
</script>

<template>
  <form
    role="search"
    aria-label="Property filters"
    class="space-y-6"
    @submit.prevent="applyPrice"
  >
    <div class="flex items-center justify-between">
      <h2
        v-if="showTitle"
        class="font-semibold text-highlighted"
      >
        Filters
      </h2>
      <UButton
        label="Reset"
        color="primary"
        variant="link"
        size="sm"
        class="ml-auto px-0"
        :disabled="activeFilterCount === 0"
        @click="emit('reset')"
      />
    </div>

    <UFormField
      label="City"
      name="city"
    >
      <template
        v-if="filters.city"
        #hint
      >
        <UButton
          label="Clear"
          color="neutral"
          variant="link"
          size="xs"
          class="px-0"
          aria-label="Clear city"
          @click="selectedCity = undefined"
        />
      </template>
      <UInputMenu
        v-model="selectedCity"
        :items="cityNames"
        icon="i-lucide-search"
        trailing-icon=""
        placeholder="All cities"
        open-on-click
        create-item
        class="w-full"
        aria-label="City"
        @create="searchTypedCity"
      >
        <template #item-trailing="{ item }">
          <span class="text-xs text-dimmed">{{ cityCounts.get(String(item)) }}</span>
        </template>
        <template #create-item-label="{ item }">
          Search "{{ item }}"
        </template>
      </UInputMenu>
    </UFormField>

    <UFormField
      label="Property type"
      name="type"
    >
      <div
        class="flex flex-wrap gap-1.5"
        role="radiogroup"
        aria-label="Property type"
      >
        <UButton
          v-for="option in typeOptions"
          :key="option.label"
          :label="option.label"
          :title="option.count === undefined ? undefined : `${option.count} listings`"
          :color="filters.type === option.value ? 'primary' : 'neutral'"
          :variant="filters.type === option.value ? 'solid' : 'outline'"
          role="radio"
          :aria-checked="filters.type === option.value"
          size="sm"
          class="rounded-full px-3"
          @click="selectType(option.value)"
        />
      </div>
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
      label="Price (€)"
      name="price"
      :error="priceError"
    >
      <div class="grid grid-cols-2 gap-2">
        <UInputNumber
          v-model="minPrice"
          :min="0"
          :step="PRICE_STEP"
          :format-options="priceFormat"
          :ui="priceInputUi"
          placeholder="No min"
          aria-label="Minimum price in EUR"
        />
        <UInputNumber
          v-model="maxPrice"
          :min="0"
          :step="PRICE_STEP"
          :format-options="priceFormat"
          :ui="priceInputUi"
          placeholder="No max"
          aria-label="Maximum price in EUR"
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
