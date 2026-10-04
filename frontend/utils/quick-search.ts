import type { PropertyType, SearchFilters } from '~/types/property'
import { formatPrice, propertyTypeLabel } from '~/utils/format'

export type QuickSearchFilters = Pick<SearchFilters, 'city' | 'minPrice' | 'maxPrice' | 'minBedrooms' | 'type'>

export interface QuickSearchResult {
  filters: QuickSearchFilters
  ignored: string[]
}

/**
 * Turns a free-text query such as "apartments in Berlin, 3+ bedrooms, max €500k" into search filters.
 *
 * This is a deterministic, rule-based parser (no LLM involved): it runs offline, needs no API key
 * and is fully unit-tested. Anything it does not understand is reported in `ignored` instead of
 * being dropped silently.
 */
export function parseQuickSearch(query: string, knownCities: string[] = []): QuickSearchResult {
  let text = ` ${query.toLowerCase().replace(/\s+/g, ' ')} `
  const filters: QuickSearchFilters = {}
  const ignored: string[] = []

  // Recognized parts are replaced by a separator, so the words around them form separate phrases.
  const consume = (pattern: RegExp): RegExpMatchArray | null => {
    const match = text.match(pattern)
    if (match) {
      text = text.replace(match[0], ' , ')
    }
    return match
  }

  const city = findCity(query, knownCities)
  if (city) {
    filters.city = city.name
    consume(new RegExp(`(?<!\\p{L})${escapeRegExp(city.match)}(?!\\p{L})`, 'u'))
  }

  // Bedrooms first, so "at least 3 bedrooms" is never mistaken for a price.
  const bedrooms = consume(BEDROOMS)
  if (bedrooms) {
    const [phrase, lowerBound, upperBound, count] = bedrooms
    const value = NUMBER_WORDS[count!] ?? Number(count)
    if (upperBound) {
      // The search only supports a minimum, so "at most 2 bedrooms" must not become "2 or more".
      ignored.push(phrase.trim())
    }
    else {
      filters.minBedrooms = lowerBound === 'more than' || lowerBound === 'over' ? value + 1 : value
    }
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
    text = text.replace(range[0], ' , ')
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

  return {
    filters: Object.fromEntries(
      Object.entries(filters).filter(([, value]) => value !== undefined),
    ) as QuickSearchFilters,
    ignored: [...ignored, ...leftoverPhrases(text)],
  }
}

export function summarizeQuickSearch(filters: QuickSearchFilters): string[] {
  const { city, type, minBedrooms, minPrice, maxPrice } = filters
  const price = minPrice !== undefined && maxPrice !== undefined
    ? `${formatPrice(minPrice)} – ${formatPrice(maxPrice)}`
    : maxPrice !== undefined
      ? `up to ${formatPrice(maxPrice)}`
      : minPrice !== undefined ? `from ${formatPrice(minPrice)}` : undefined

  return [
    type && propertyTypeLabel(type),
    city,
    minBedrooms !== undefined ? `${minBedrooms}+ bedrooms` : undefined,
    price,
  ].filter((part): part is string => !!part)
}

const NUMBER_WORDS: Record<string, number> = { one: 1, two: 2, three: 3, four: 4, five: 5, six: 6 }

/** "3 bedrooms", "3+ beds", "at least three bedrooms", "more than 2 rooms", "at most 2 bedrooms". */
const BEDROOMS = new RegExp(
  String.raw`(?:(at least|min(?:imum)?(?: of)?|more than|over)\s+|(at most|max(?:imum)?(?: of)?|up to|no more than|less than|fewer than|under|below)\s+)?`
  + String.raw`(?<!\p{L})(\d+|${Object.keys(NUMBER_WORDS).join('|')})\s*\+?\s*(?:-\s*)?(?:bed(?:room)?s?|br|rooms?)\b`,
  'u',
)

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

/** An amount such as "500k", "€1.2m", "500,000 €" or "500.000". Captures the number and the suffix. */
const AMOUNT = String.raw`€?\s*(\d+(?:[.,]\d+)*)\s*(k|m|mio|million|thousand)?\b\s*(?:€|eur(?:os?)?)?`

/** Words allowed between a price keyword and the amount, as in "a maximum price of €500,000". */
const FILLER = String.raw`(?:\s*(?:price|budget|of|is|at|:))*\s*`

/** Words that carry no filter on their own ("a house in Berlin with 3 bedrooms" is fully understood). */
const STOP_WORDS = new Set([
  'a', 'an', 'the', 'in', 'at', 'on', 'of', 'for', 'with', 'and', 'or', 'to', 'least', 'near', 'around',
  'i', 'me', 'we', 'want', 'need', 'looking', 'search', 'find', 'show', 'some', 'any', 'please',
  'property', 'properties', 'home', 'homes', 'listing', 'listings', 'sale', 'buy', 'price', 'eur', 'euros', '€',
])

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

function findCity(query: string, knownCities: string[]): { name: string, match: string } | undefined {
  const text = query.toLowerCase()
  const candidates: [string, string][] = [
    ...knownCities.map(city => [city.toLowerCase(), city] as [string, string]),
    ...Object.entries(CITY_ALIASES),
  ]

  for (const [needle, city] of candidates) {
    if (new RegExp(`(?<!\\p{L})${escapeRegExp(needle)}(?!\\p{L})`, 'u').test(text)) {
      return { name: city, match: needle }
    }
  }

  // Unknown city (e.g. facets not loaded yet): fall back to "in <Name>".
  const name = query.match(/\bin\s+(\p{Lu}[\p{L}-]+)/u)?.[1]
  return name ? { name, match: name.toLowerCase() } : undefined
}

function leftoverPhrases(text: string): string[] {
  const phrases: string[] = []
  let current: string[] = []
  const flush = () => {
    if (current.length) {
      phrases.push(current.join(' '))
      current = []
    }
  }

  for (const token of text.split(' ')) {
    const word = token.replace(/^[^\p{L}\p{N}€]+|[^\p{L}\p{N}€]+$/gu, '')
    if (word === '' || STOP_WORDS.has(word)) {
      flush()
    }
    else {
      current.push(word)
    }
    if (/[,;:!?]$/.test(token)) {
      flush()
    }
  }
  flush()

  return phrases
}

function escapeRegExp(value: string): string {
  return value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
}
