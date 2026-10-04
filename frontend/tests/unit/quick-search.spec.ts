import { describe, expect, it } from 'vitest'
import { parseQuickSearch, summarizeQuickSearch } from '~/utils/quick-search'

const CITIES = ['Berlin', 'Cologne', 'Frankfurt', 'Hamburg', 'Munich']

const filtersOf = (query: string) => parseQuickSearch(query, CITIES).filters

describe('parseQuickSearch', () => {
  it('parses the challenge example', () => {
    expect(parseQuickSearch('Apartments in Berlin, at least 3 bedrooms and a maximum price of €500,000', CITIES)).toEqual({
      filters: { city: 'Berlin', type: 'apartment', minBedrooms: 3, maxPrice: 500000 },
      ignored: [],
    })
  })

  it('understands short forms', () => {
    expect(filtersOf('apartments in berlin, 3+ bedrooms, max €500k')).toEqual({
      city: 'Berlin',
      type: 'apartment',
      minBedrooms: 3,
      maxPrice: 500000,
    })
    expect(filtersOf('2 bed flat hamburg under 1.2m')).toEqual({
      city: 'Hamburg',
      type: 'apartment',
      minBedrooms: 2,
      maxPrice: 1200000,
    })
  })

  it('parses price ranges and minimum prices', () => {
    expect(filtersOf('house between 800k and 1.5m')).toEqual({ type: 'house', minPrice: 800000, maxPrice: 1500000 })
    expect(filtersOf('penthouse from 1,000,000 €')).toEqual({ type: 'penthouse', minPrice: 1000000 })
    expect(filtersOf('studio up to 250.000')).toEqual({ type: 'studio', maxPrice: 250000 })
  })

  it('maps German city names and number words', () => {
    expect(parseQuickSearch('townhouse in München with four bedrooms', CITIES)).toEqual({
      filters: { city: 'Munich', type: 'townhouse', minBedrooms: 4 },
      ignored: [],
    })
    expect(filtersOf('Köln')).toEqual({ city: 'Cologne' })
  })

  it('does not mistake bedroom counts for prices', () => {
    expect(filtersOf('at least 3 bedrooms')).toEqual({ minBedrooms: 3 })
  })

  it('reads "more than" as a strict lower bound for bedrooms', () => {
    expect(filtersOf('house with more than 2 bedrooms')).toEqual({ type: 'house', minBedrooms: 3 })
  })

  it('never turns a maximum number of bedrooms into a minimum', () => {
    expect(parseQuickSearch('apartments in Berlin, at most 2 bedrooms', CITIES)).toEqual({
      filters: { city: 'Berlin', type: 'apartment' },
      ignored: ['at most 2 bedrooms'],
    })
    expect(parseQuickSearch('flat under 3 bedrooms', CITIES).filters).toEqual({ type: 'apartment' })
  })

  it('reports the parts it does not understand', () => {
    expect(parseQuickSearch('Imóveis em Berlin, com pelo menos 3 quartos e preço máximo de €500.000.', CITIES)).toEqual({
      filters: { city: 'Berlin' },
      ignored: ['imóveis em', 'com pelo menos 3 quartos e preço máximo de €500.000'],
    })
    expect(parseQuickSearch('cheap house in Hamburg with a garden', CITIES)).toEqual({
      filters: { city: 'Hamburg', type: 'house' },
      ignored: ['cheap', 'garden'],
    })
  })

  it('keeps an unknown city written as "in <City>"', () => {
    expect(filtersOf('flats in Paris')).toEqual({ city: 'Paris', type: 'apartment' })
  })

  it('returns no filters for unrelated text', () => {
    expect(parseQuickSearch('something nice', CITIES)).toEqual({ filters: {}, ignored: ['something nice'] })
  })
})

describe('summarizeQuickSearch', () => {
  it('describes the recognized filters', () => {
    expect(summarizeQuickSearch({ city: 'Berlin', type: 'apartment', minBedrooms: 3, maxPrice: 500000 }))
      .toEqual(['Apartment', 'Berlin', '3+ bedrooms', 'up to €500,000'])
    expect(summarizeQuickSearch({ minPrice: 800000, maxPrice: 1500000 })).toEqual(['€800,000 – €1,500,000'])
    expect(summarizeQuickSearch({ minPrice: 1000000 })).toEqual(['from €1,000,000'])
  })
})
