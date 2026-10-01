import type { SearchFilters } from '~/types/property'
import { DEFAULT_SORT } from '~/utils/search-query'

/**
 * Arguments of the MCP `search_properties` tool for the current search.
 * They use the same names as the REST query parameters.
 */
export function toSearchPropertiesArguments(filters: SearchFilters): Record<string, string | number> {
  const args: Record<string, string | number> = {}

  if (filters.city) args.city = filters.city
  if (filters.minPrice !== undefined) args.minPrice = filters.minPrice
  if (filters.maxPrice !== undefined) args.maxPrice = filters.maxPrice
  if (filters.minBedrooms) args.minBedrooms = filters.minBedrooms
  if (filters.type) args.type = filters.type
  if (filters.sort !== DEFAULT_SORT) args.sort = filters.sort

  return args
}

export function formatSearchPropertiesCall(filters: SearchFilters): string {
  const args = Object.entries(toSearchPropertiesArguments(filters))
    .map(([name, value]) => `${name}: ${JSON.stringify(value)}`)
    .join(', ')

  return `search_properties({ ${args} })`.replace('({  })', '({})')
}
