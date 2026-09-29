import { describe, expect, it } from 'vitest'
import { describeApiError } from '~/utils/api-error'

describe('describeApiError', () => {
  it('lists validation violations from problem details', () => {
    const description = describeApiError({
      statusCode: 422,
      data: {
        status: 422,
        title: 'Unprocessable Content',
        detail: 'The request contains invalid parameters.',
        violations: [{ field: 'maxPrice', message: 'The maximum price must be greater than or equal to the minimum price.' }],
      },
    })

    expect(description.title).toBe('Some search filters are invalid')
    expect(description.details).toEqual(['The maximum price must be greater than or equal to the minimum price.'])
  })

  it('reports an unreachable backend as service unavailable', () => {
    expect(describeApiError({ statusCode: 502, data: null }).title).toBe('Service unavailable')
    expect(describeApiError(new TypeError('fetch failed')).title).toBe('Service unavailable')
  })

  it('falls back to a generic message', () => {
    const description = describeApiError({ statusCode: 500, data: { status: 500, detail: 'An unexpected error occurred.' } })

    expect(description.title).toBe('Something went wrong')
    expect(description.message).toBe('An unexpected error occurred.')
  })
})
