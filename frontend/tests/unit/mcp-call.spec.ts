import { describe, expect, it } from 'vitest'
import { formatSearchPropertiesCall } from '~/utils/mcp-call'

describe('formatSearchPropertiesCall', () => {
  it('formats the equivalent MCP tool call with the REST parameter names', () => {
    expect(formatSearchPropertiesCall({ city: 'Berlin', minBedrooms: 3, maxPrice: 500000, sort: 'newest', page: 2 }))
      .toBe('search_properties({ city: "Berlin", maxPrice: 500000, minBedrooms: 3 })')
  })

  it('includes a non-default sort but never the page', () => {
    expect(formatSearchPropertiesCall({ type: 'house', sort: 'price_asc', page: 1 }))
      .toBe('search_properties({ type: "house", sort: "price_asc" })')
  })

  it('handles an empty search', () => {
    expect(formatSearchPropertiesCall({ sort: 'newest', page: 1 })).toBe('search_properties({})')
  })
})
