import { computed } from 'vue'
import type { PropertyListResponse, SearchFilters } from '~/types/property'

export const RESULTS_PER_PAGE = 12

/**
 * Search state + results for the listing page.
 *
 * The URL query is the single source of truth: filters are parsed from it and every
 * change is a navigation. This gives shareable links, working back/forward buttons and
 * SSR of the exact same search on reload for free.
 */
export function usePropertySearch() {
  const route = useRoute()
  const router = useRouter()

  const filters = computed<SearchFilters>(() => parseSearchQuery(route.query))

  const apiQuery = computed(() => ({
    ...toSearchQuery(filters.value),
    perPage: String(RESULTS_PER_PAGE),
  }))

  const { data, status, error, refresh } = useFetch<PropertyListResponse>('/api/properties', {
    query: apiQuery,
  })

  /** Applies filter changes; any change other than paging brings the user back to page 1. */
  async function applyFilters(changes: Partial<SearchFilters>): Promise<void> {
    const next: SearchFilters = { ...filters.value, ...changes }
    if (!('page' in changes)) {
      next.page = 1
    }

    await router.push({ query: toSearchQuery(next) })
  }

  async function goToPage(page: number) {
    await applyFilters({ page })
    if (import.meta.client) {
      window.scrollTo({ top: 0, behavior: 'smooth' })
    }
  }

  async function resetFilters(): Promise<void> {
    await router.push({ query: {} })
  }

  return {
    filters,
    data,
    status,
    error,
    refresh,
    isLoading: computed(() => status.value === 'pending'),
    applyFilters,
    goToPage,
    resetFilters,
  }
}
