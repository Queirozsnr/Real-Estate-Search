import type { PropertyType, SearchFilters } from '~/types/property'

export type QuickSearchFilters = Pick<SearchFilters, 'city' | 'minPrice' | 'maxPrice' | 'minBedrooms' | 'type'>

/**
 * Turns a free-text query such as "apartments in Berlin, 3+ bedrooms, max €500k" into search filters.
 *
 * This is a deterministic, rule-based parser (no LLM involved): it runs offline, needs no API key
 * and is fully unit-tested. Unrecognized words are simply ignored.
 */
export function parseQuickSearch(query: string, knownCities: string[] = []): QuickSearchFilters {
  let text = ` ${query.toLowerCase().replace(/\s+/g, ' ')} `
  const filters: QuickSearchFilters = {}

  const consume = (pattern: RegExp): RegExpMatchArray | null => {
    const match = text.match(pattern)
    if (match) {
      text = text.replace(match[0], ' ')
    }
    return match
  }

  filters.city = findCity(query, knownCities)
  if (filters.city) {
    consume(new RegExp(`(?<!\\p{L})${escapeRegExp(filters.city.toLowerCase())}(?!\\p{L})`, 'u'))
  }

  // Bedrooms first, so "at least 3 bedrooms" is never mistaken for a price.
  const bedrooms = consume(new RegExp(`(\\d+|${Object.keys(NUMBER_WORDS).join('|')})\\s*\\+?\\s*(?:-\\s*)?(?:bed(?:room)?s?|br|rooms?)\\b`))
  if (bedrooms?.[1]) {
    filters.minBedrooms = NUMBER_WORDS[bedrooms[1]] ?? Number(bedrooms[1])
  }

  for (const [type, pattern] of TYPE_PATTERNS) {
    if (consume(pattern)) {
      filters.type = type
      break
    }
  }

  const range = text.match(new RegExp(`(?:between\\s*)?${AMOUNT}\\s*(?:-|–|to|and)\\s*${AMOUNT}`))
  const [low, high] = range ? [toPrice(range[1], range[2]), toPrice(range[3], range[4])] : []
  if (range && low !== undefined && high !== undefined) {
    text = text.replace(range[0], ' ')
    filters.minPrice = Math.min(low, high)
    filters.maxPrice = Math.max(low, high)
  }

  const max = consume(new RegExp(`(?:max(?:imum)?|under|below|up to|less than|at most|cheaper than|budget|<=?|≤)${FILLER}${AMOUNT}`))
  if (max) {
    filters.maxPrice = toPrice(max[1], max[2]) ?? filters.maxPrice
  }

  const min = consume(new RegExp(`(?:min(?:imum)?|from|over|above|more than|at least|>=?|≥)${FILLER}${AMOUNT}`))
  if (min) {
    filters.minPrice = toPrice(min[1], min[2]) ?? filters.minPrice
  }

  return Object.fromEntries(
    Object.entries(filters).filter(([, value]) => value !== undefined),
  ) as QuickSearchFilters
}

const TYPE_PATTERNS: [PropertyType, RegExp][] = [
  ['penthouse', /\bpenthouses?\b/],
  ['townhouse', /\b(?:town ?houses?|terraced houses?|row houses?)\b/],
  ['studio', /\bstudios?\b/],
  ['apartment', /\b(?:apartments?|flats?|condos?)\b/],
  ['house', /\b(?:houses?|villas?)\b/],
]

const CITY_ALIASES: Record<string, string> = {
  münchen: 'Munich',
  muenchen: 'Munich',
  köln: 'Cologne',
  koeln: 'Cologne',
}

const NUMBER_WORDS: Record<string, number> = { one: 1, two: 2, three: 3, four: 4, five: 5, six: 6 }

/** An amount such as "500k", "€1.2m", "500,000 €" or "500.000". Captures the number and the suffix. */
const AMOUNT = String.raw`€?\s*(\d+(?:[.,]\d+)*)\s*(k|m|mio|million|thousand)?\b\s*(?:€|eur(?:os?)?)?`

/** Words allowed between a price keyword and the amount, as in "a maximum price of €500,000". */
const FILLER = String.raw`(?:\s*(?:price|budget|of|is|at|:))*\s*`

const MINIMUM_PRICE = 1000

function toPrice(number: string | undefined, suffix: string | undefined): number | undefined {
  if (!number) {
    return undefined
  }

  // "500.000" and "500,000" use thousands separators; "1.2" and "1,5" are decimals.
  const value = /^\d{1,3}(?:[.,]\d{3})+$/.test(number)
    ? Number(number.replace(/[.,]/g, ''))
    : Number(number.replace(',', '.'))

  const multiplier = suffix === 'k' || suffix === 'thousand' ? 1_000 : suffix ? 1_000_000 : 1
  const price = Math.round(value * multiplier)

  // Small numbers are not prices (e.g. "under 3" in "under 3 floors").
  return Number.isFinite(price) && price >= MINIMUM_PRICE ? price : undefined
}

function findCity(query: string, knownCities: string[]): string | undefined {
  const text = query.toLowerCase()
  const candidates: [string, string][] = [
    ...knownCities.map(city => [city.toLowerCase(), city] as [string, string]),
    ...Object.entries(CITY_ALIASES),
  ]

  for (const [needle, city] of candidates) {
    if (new RegExp(`(?<!\\p{L})${escapeRegExp(needle)}(?!\\p{L})`, 'u').test(text)) {
      return city
    }
  }

  // Unknown city (e.g. facets not loaded yet): fall back to "in <Name>".
  return query.match(/\bin\s+(\p{Lu}[\p{L}-]+)/u)?.[1]
}

function escapeRegExp(value: string): string {
  return value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
}
