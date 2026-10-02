import { describe, expect, it } from 'vitest'
import { mountSuspended } from '@nuxt/test-utils/runtime'
import PriceComparison from '~/components/property/PriceComparison.vue'

const market = (differencePercent: number) => ({ city: 'Berlin', listings: 9, averagePricePerSquareMetre: 6147, differencePercent })

describe('PriceComparison', () => {
  it('highlights a price below the city average', async () => {
    const wrapper = await mountSuspended(PriceComparison, { props: { market: market(-14) } })

    expect(wrapper.text()).toContain('14% below the Berlin average')
    expect(wrapper.text()).toContain('Average €6,147/m² across 9 listings')
  })

  it('flags a price above the city average', async () => {
    const wrapper = await mountSuspended(PriceComparison, { props: { market: market(13) } })

    expect(wrapper.text()).toContain('13% above the Berlin average')
  })

  it('treats small differences as in line with the average', async () => {
    const wrapper = await mountSuspended(PriceComparison, { props: { market: market(-2) } })

    expect(wrapper.text()).toContain('In line with the Berlin average')
  })
})
