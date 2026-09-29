import { describe, expect, it } from 'vitest'
import { mountSuspended } from '@nuxt/test-utils/runtime'
import PropertyCard from '~/components/property/PropertyCard.vue'
import type { PropertySummary } from '~/types/property'

const property: PropertySummary = {
  id: 5,
  title: 'Renovated Altbau apartment in Neukölln',
  type: 'apartment',
  city: 'Berlin',
  district: 'Neukölln',
  price: 459000,
  bedrooms: 3,
  bathrooms: 1,
  livingArea: 88,
  imageUrl: null,
  listedAt: '2026-09-10',
}

describe('PropertyCard', () => {
  it('renders the key information and links to the detail page', async () => {
    const wrapper = await mountSuspended(PropertyCard, { props: { property } })

    expect(wrapper.text()).toContain('€459,000')
    expect(wrapper.text()).toContain('Renovated Altbau apartment in Neukölln')
    expect(wrapper.text()).toContain('Neukölln, Berlin')
    expect(wrapper.text()).toContain('3 bedrooms')
    expect(wrapper.text()).toContain('88 m²')
    expect(wrapper.find('a').attributes('href')).toBe('/properties/5')
  })

  it('shows a placeholder when the property has no image', async () => {
    const wrapper = await mountSuspended(PropertyCard, { props: { property } })

    expect(wrapper.find('img').exists()).toBe(false)
    expect(wrapper.find('[role="img"]').attributes('aria-label')).toContain('image unavailable')
  })
})
