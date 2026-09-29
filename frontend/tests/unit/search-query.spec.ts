import { describe, expect, it } from 'vitest'
import { countActiveFilters, parseSearchQuery, toSearchQuery } from '~/utils/search-query'

describe('parseSearchQuery', () => {
  it('returns defaults for an empty query', () => {
    expect(parseSearchQuery({})).toEqual({ sort: 'newest', page: 1 })
  })

  it('parses every supported filter', () => {
    expect(parseSearchQuery({
      city: 'Berlin',
      minPrice: '100000',
      maxPrice: '500000',
      minBedrooms: '3',
      type: 'apartment',
      sort: 'price_asc',
      page: '2',
    })).toEqual({
      city: 'Berlin',
      minPrice: 100000,
      maxPrice: 500000,
      minBedrooms: 3,
      type: 'apartment',
      sort: 'price_asc',
      page: 2,
    })
  })

  it('drops invalid values instead of failing (hand-edited URLs)', () => {
    expect(parseSearchQuery({
      city: '   ',
      minPrice: 'cheap',
      maxPrice: '-5',
      type: 'castle',
      sort: 'random',
      page: '0',
    })).toEqual({ sort: 'newest', page: 1 })
  })

  it('uses the first value of repeated parameters', () => {
    expect(parseSearchQuery({ city: ['Hamburg', 'Berlin'] }).city).toBe('Hamburg')
  })
})

describe('toSearchQuery', () => {
  it('omits empty values and defaults to keep URLs short', () => {
    expect(toSearchQuery({ sort: 'newest', page: 1, minBedrooms: 0 })).toEqual({})
  })

  it('round-trips with parseSearchQuery', () => {
    const filters = { city: 'Munich', maxPrice: 900000, minBedrooms: 2, type: 'house' as const, sort: 'price_desc' as const, page: 3 }

    expect(parseSearchQuery(toSearchQuery(filters))).toEqual(filters)
  })
})

describe('countActiveFilters', () => {
  it('counts filters but not sort or page', () => {
    expect(countActiveFilters({ sort: 'price_asc', page: 4 })).toBe(0)
    expect(countActiveFilters({ city: 'Berlin', minBedrooms: 3, maxPrice: 500000, sort: 'newest', page: 1 })).toBe(3)
  })
})
