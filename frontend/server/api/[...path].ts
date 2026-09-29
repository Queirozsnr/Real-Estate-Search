import type { ProblemDetails } from '~/types/property'

/**
 * Backend-for-frontend proxy: forwards /api/** to the Symfony API.
 *
 * The browser only ever talks to the Nuxt origin, so no CORS configuration is needed and
 * the backend address stays a server-side concern (NUXT_API_BASE_URL at runtime).
 */
export default defineEventHandler(async (event) => {
  const { apiBaseUrl } = useRuntimeConfig(event)

  try {
    return await proxyRequest(event, `${apiBaseUrl}${event.path}`)
  }
  catch (error) {
    console.error(`[api-proxy] ${event.method} ${event.path} failed:`, error)

    // Same RFC 9457 shape as the API's own errors, so the UI handles both identically.
    setResponseStatus(event, 502)
    setResponseHeader(event, 'Content-Type', 'application/problem+json')

    return {
      type: 'about:blank',
      title: 'Bad Gateway',
      status: 502,
      detail: 'The property service is currently unavailable. Please try again in a moment.',
    } satisfies ProblemDetails
  }
})
