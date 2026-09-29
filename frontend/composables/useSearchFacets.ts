import type { SearchFacets } from '~/types/property'

export function useSearchFacets() {
  return useFetch<SearchFacets>('/api/properties/facets', {
    key: 'search-facets',
    // Facets are optional for the form (it degrades to free inputs), so never block navigation on them.
    lazy: true,
  })
}
