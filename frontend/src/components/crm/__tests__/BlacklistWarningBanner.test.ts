import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'

import BlacklistWarningBanner from '@/components/crm/BlacklistWarningBanner.vue'

describe('BlacklistWarningBanner', () => {
  it('renders the warning message when visible', () => {
    const wrapper = mount(BlacklistWarningBanner, {
      props: { visible: true },
    })

    expect(wrapper.text()).toContain('liste noire')
  })

  it('can be dismissed by the user', async () => {
    const wrapper = mount(BlacklistWarningBanner, {
      props: { visible: true },
    })

    await wrapper.get('button').trigger('click')

    expect(wrapper.text()).toBe('')
  })
})
