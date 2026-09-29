import type { LocationQuery, LocationQueryValue } from 'vue-router'
import { PROPERTY_SORTS, PROPERTY_TYPES } from '~/types/property'
import type { PropertySort, PropertyType, SearchFilters } from '~/types/property'

export const DEFAULT_SORT: PropertySort = 'newest'

export function defaultFilters(): SearchFilters {
  return { sort: DEFAULT_SORT, page: 1 }
}

function first(value: LocationQueryValue | LocationQueryValue[] | undefined): string | undefined {
  const raw = Array.isArray(value) ? value[0] : value
  const trimmed = raw?.trim()
  return trimmed ? trimmed : undefined
}

function toNonNegativeInt(value: string | undefined): number | undefined {
  if (value === undefined || !/^\d+$/.test(value)) {
    return undefined
  }
  return Number.parseInt(value, 10)
}

function oneOf<T extends string>(allowed: readonly T[], value: string | undefined): T | undefined {
  return allowed.includes(value as T) ? (value as T) : undefined
}

/**
 * Reads the search filters from the URL query.
 *
 * Parsing is deliberately lenient: a hand-edited URL with an unknown type or a
 * non-numeric price just drops that filter instead of breaking the page.
 */
export function parseSearchQuery(query: LocationQuery): SearchFilters {
  return {
    city: first(query.city),
    minPrice: toNonNegativeInt(first(query.minPrice)),
    maxPrice: toNonNegativeInt(first(query.maxPrice)),
    minBedrooms: toNonNegativeInt(first(query.minBedrooms)),
    type: oneOf<PropertyType>(PROPERTY_TYPES, first(query.type)),
    sort: oneOf<PropertySort>(PROPERTY_SORTS, first(query.sort)) ?? DEFAULT_SORT,
    page: Math.max(1, toNonNegativeInt(first(query.page)) ?? 1),
  }
}

/**
 * Serializes filters to query parameters, omitting empty values and defaults
 * to keep URLs short. The same shape is used for the URL and the API request.
 */
export function toSearchQuery(filters: SearchFilters): Record<string, string> {
  const query: Record<string, string> = {}

  if (filters.city) query.city = filters.city
  if (filters.minPrice !== undefined) query.minPrice = String(filters.minPrice)
  if (filters.maxPrice !== undefined) query.maxPrice = String(filters.maxPrice)
  if (filters.minBedrooms !== undefined && filters.minBedrooms > 0) query.minBedrooms = String(filters.minBedrooms)
  if (filters.type) query.type = filters.type
  if (filters.sort !== DEFAULT_SORT) query.sort = filters.sort
  if (filters.page > 1) query.page = String(filters.page)

  return query
}

/** Number of active filters (sort and page are not filters). */
export function countActiveFilters(filters: SearchFilters): number {
  return [filters.city, filters.minPrice, filters.maxPrice, filters.minBedrooms || undefined, filters.type]
    .filter(value => value !== undefined)
    .length
}
