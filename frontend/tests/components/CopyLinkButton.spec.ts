import { afterEach, describe, expect, it, vi } from 'vitest'
import { mountSuspended } from '@nuxt/test-utils/runtime'
import CopyLinkButton from '~/components/common/CopyLinkButton.vue'

describe('CopyLinkButton', () => {
  afterEach(() => {
    vi.restoreAllMocks()
  })

  it('copies the page address and confirms it', async () => {
    const writeText = vi.spyOn(navigator.clipboard, 'writeText').mockResolvedValue()
    const wrapper = await mountSuspended(CopyLinkButton)

    expect(wrapper.text()).toContain('Copy link')

    await wrapper.find('button').trigger('click')
    await vi.waitFor(() => expect(wrapper.text()).toContain('Link copied'))

    expect(writeText).toHaveBeenCalledWith(window.location.href)
  })
})
