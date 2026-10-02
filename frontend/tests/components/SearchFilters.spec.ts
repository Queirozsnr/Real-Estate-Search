import { describe, expect, it } from 'vitest'
import { mountSuspended } from '@nuxt/test-utils/runtime'
import SearchFilters from '~/components/search/SearchFilters.vue'

describe('SearchFilters', () => {
  it('applies the minimum bedrooms filter when an option is picked', async () => {
    const wrapper = await mountSuspended(SearchFilters, { props: { filters: { sort: 'newest', page: 1 } } })

    const threePlus = wrapper.findAll('button[role="radio"]').find(button => button.text() === '3+')
    await threePlus!.trigger('click')

    expect(wrapper.emitted('apply')).toEqual([[{ minBedrooms: 3 }]])
  })

  it('applies and clears the property type with chips', async () => {
    const wrapper = await mountSuspended(SearchFilters, { props: { filters: { sort: 'newest', page: 1, type: 'house' } } })
    const chips = wrapper.findAll('[role="radiogroup"][aria-label="Property type"] button')

    expect(chips.find(chip => chip.text() === 'House')!.attributes('aria-checked')).toBe('true')

    await chips.find(chip => chip.text() === 'Studio')!.trigger('click')
    await chips.find(chip => chip.text() === 'All')!.trigger('click')

    expect(wrapper.emitted('apply')).toEqual([[{ type: 'studio' }], [{ type: undefined }]])
  })

  it('clears the bedrooms filter with "Any"', async () => {
    const wrapper = await mountSuspended(SearchFilters, { props: { filters: { sort: 'newest', page: 1, minBedrooms: 2 } } })

    const any = wrapper.findAll('button[role="radio"]').find(button => button.text() === 'Any')
    await any!.trigger('click')

    expect(wrapper.emitted('apply')).toEqual([[{ minBedrooms: undefined }]])
  })

  it('enables "Reset" only when filters are active', async () => {
    const withoutFilters = await mountSuspended(SearchFilters, { props: { filters: { sort: 'newest', page: 1 } } })
    const disabledReset = withoutFilters.findAll('button').find(button => button.text() === 'Reset')
    expect(disabledReset!.attributes('disabled')).toBeDefined()

    const withFilters = await mountSuspended(SearchFilters, { props: { filters: { sort: 'newest', page: 1, city: 'Berlin' } } })
    const reset = withFilters.findAll('button').find(button => button.text() === 'Reset')
    await reset!.trigger('click')

    expect(withFilters.emitted('reset')).toHaveLength(1)
  })

  it('clears the city', async () => {
    const wrapper = await mountSuspended(SearchFilters, { props: { filters: { sort: 'newest', page: 1, city: 'Berlin' } } })

    await wrapper.find('button[aria-label="Clear city"]').trigger('click')

    expect(wrapper.emitted('apply')).toEqual([[{ city: undefined }]])
  })

  it('shows a validation error and does not apply an inverted price range', async () => {
    const wrapper = await mountSuspended(SearchFilters, {
      props: { filters: { sort: 'newest', page: 1, minPrice: 800000, maxPrice: 200000 } },
    })

    expect(wrapper.text()).toContain('The minimum price cannot be higher than the maximum price.')
    expect(wrapper.emitted('apply')).toBeUndefined()
  })
})
