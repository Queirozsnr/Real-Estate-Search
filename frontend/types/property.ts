/**
 * Types mirroring the Symfony API contract (see backend/src/View and backend/src/Search).
 */

export const PROPERTY_TYPES = ['apartment', 'house', 'studio', 'penthouse', 'townhouse'] as const
export type PropertyType = (typeof PROPERTY_TYPES)[number]

export const PROPERTY_SORTS = ['newest', 'price_asc', 'price_desc', 'area_desc'] as const
export type PropertySort = (typeof PROPERTY_SORTS)[number]

export interface PropertySummary {
  id: number
  title: string
  type: PropertyType
  city: string
  district: string
  price: number
  pricePerSquareMetre: number
  bedrooms: number
  bathrooms: number
  livingArea: number
  imageUrl: string | null
  listedAt: string
}

export interface PropertyDetails extends Omit<PropertySummary, 'imageUrl'> {
  description: string
  address: string
  yearBuilt: number | null
  features: string[]
  images: string[]
  location: { latitude: number, longitude: number }
}

export interface PaginationMeta {
  total: number
  page: number
  perPage: number
  totalPages: number
  filters: Partial<Record<keyof SearchFilters, string | number>>
  sort: PropertySort
}

export interface PropertyListResponse {
  data: PropertySummary[]
  meta: PaginationMeta
}

export interface PropertyResponse {
  data: PropertyDetails
}

export interface SearchFacets {
  cities: { name: string, count: number }[]
  types: { value: PropertyType, label: string, count: number }[]
  minPrice: number
  maxPrice: number
  maxBedrooms: number
}

/** RFC 9457 problem details returned by the API on errors. */
export interface ProblemDetails {
  type: string
  title: string
  status: number
  detail: string
  violations?: { field: string, message: string }[]
}

/** Search state of the UI. It lives in the URL query so searches are shareable and survive reloads. */
export interface SearchFilters {
  city?: string
  minPrice?: number
  maxPrice?: number
  minBedrooms?: number
  type?: PropertyType
  sort: PropertySort
  page: number
}
