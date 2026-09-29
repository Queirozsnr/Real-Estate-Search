import type { PropertySort, PropertyType } from '~/types/property'

const LOCALE = 'en-GB'

const priceFormatter = new Intl.NumberFormat(LOCALE, {
  style: 'currency',
  currency: 'EUR',
  maximumFractionDigits: 0,
})

const dateFormatter = new Intl.DateTimeFormat(LOCALE, { day: 'numeric', month: 'short', year: 'numeric', timeZone: 'UTC' })

export function formatPrice(value: number): string {
  return priceFormatter.format(value)
}

export function formatArea(squareMetres: number): string {
  return `${squareMetres} m²`
}

export function formatDate(isoDate: string): string {
  return dateFormatter.format(new Date(`${isoDate}T00:00:00Z`))
}

export function formatBedrooms(count: number): string {
  if (count === 0) return 'Studio'
  return count === 1 ? '1 bedroom' : `${count} bedrooms`
}

export function formatBathrooms(count: number): string {
  return count === 1 ? '1 bathroom' : `${count} bathrooms`
}

const TYPE_LABELS: Record<PropertyType, string> = {
  apartment: 'Apartment',
  house: 'House',
  studio: 'Studio',
  penthouse: 'Penthouse',
  townhouse: 'Townhouse',
}

export function propertyTypeLabel(type: PropertyType): string {
  return TYPE_LABELS[type]
}

export const SORT_LABELS: Record<PropertySort, string> = {
  newest: 'Newest first',
  price_asc: 'Price: low to high',
  price_desc: 'Price: high to low',
  area_desc: 'Largest first',
}

export function capitalize(value: string): string {
  return value.charAt(0).toUpperCase() + value.slice(1)
}
