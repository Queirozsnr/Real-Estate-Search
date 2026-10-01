import { describe, expect, it } from 'vitest'
import { parseQuickSearch } from '~/utils/quick-search'

const CITIES = ['Berlin', 'Cologne', 'Frankfurt', 'Hamburg', 'Munich']

describe('parseQuickSearch', () => {
  it('parses the challenge example', () => {
    expect(parseQuickSearch('Apartments in Berlin, at least 3 bedrooms and a maximum price of €500,000', CITIES)).toEqual({
      city: 'Berlin',
      type: 'apartment',
      minBedrooms: 3,
      maxPrice: 500000,
    })
  })

  it('understands short forms', () => {
    expect(parseQuickSearch('apartments in berlin, 3+ bedrooms, max €500k', CITIES)).toEqual({
      city: 'Berlin',
      type: 'apartment',
      minBedrooms: 3,
      maxPrice: 500000,
    })
    expect(parseQuickSearch('2 bed flat hamburg under 1.2m', CITIES)).toEqual({
      city: 'Hamburg',
      type: 'apartment',
      minBedrooms: 2,
      maxPrice: 1200000,
    })
  })

  it('parses price ranges and minimum prices', () => {
    expect(parseQuickSearch('house between 800k and 1.5m', CITIES)).toEqual({ type: 'house', minPrice: 800000, maxPrice: 1500000 })
    expect(parseQuickSearch('penthouse from 1,000,000 €', CITIES)).toEqual({ type: 'penthouse', minPrice: 1000000 })
    expect(parseQuickSearch('studio up to 250.000', CITIES)).toEqual({ type: 'studio', maxPrice: 250000 })
  })

  it('maps German city names and number words', () => {
    expect(parseQuickSearch('townhouse in München with four bedrooms', CITIES)).toEqual({
      city: 'Munich',
      type: 'townhouse',
      minBedrooms: 4,
    })
    expect(parseQuickSearch('Köln', CITIES)).toEqual({ city: 'Cologne' })
  })

  it('does not mistake bedroom counts for prices', () => {
    expect(parseQuickSearch('at least 3 bedrooms', CITIES)).toEqual({ minBedrooms: 3 })
  })

  it('keeps an unknown city written as "in <City>"', () => {
    expect(parseQuickSearch('flats in Paris', CITIES)).toEqual({ city: 'Paris', type: 'apartment' })
  })

  it('returns no filters for unrelated text', () => {
    expect(parseQuickSearch('something nice please', CITIES)).toEqual({})
  })
})
