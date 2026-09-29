import type { ProblemDetails } from '~/types/property'

export interface ErrorDescription {
  title: string
  message: string
  details: string[]
}

interface FetchErrorLike {
  statusCode?: number
  status?: number
  data?: Partial<ProblemDetails> | null
}

/**
 * Surfaces the RFC 9457 problem details sent by the API (and the proxy) when available.
 */
export function describeApiError(error: unknown): ErrorDescription {
  const fetchError = (error ?? {}) as FetchErrorLike
  const problem = fetchError.data ?? undefined
  const status = problem?.status ?? fetchError.statusCode ?? fetchError.status

  if (problem?.violations?.length) {
    return {
      title: 'Some search filters are invalid',
      message: problem.detail ?? 'Please check your filters and try again.',
      details: problem.violations.map(violation => violation.message),
    }
  }

  if (status === undefined || status === 502 || status === 503 || status === 504) {
    return {
      title: 'Service unavailable',
      message: problem?.detail ?? 'We could not reach the property service. Check your connection and try again.',
      details: [],
    }
  }

  return {
    title: 'Something went wrong',
    message: problem?.detail ?? 'An unexpected error occurred. Please try again.',
    details: [],
  }
}
