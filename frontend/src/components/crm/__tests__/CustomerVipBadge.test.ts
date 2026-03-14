import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'

import CustomerVipBadge from '@/components/crm/CustomerVipBadge.vue'

describe('CustomerVipBadge', () => {
  it('renders the vip badge when enabled', () => {
    const wrapper = mount(CustomerVipBadge, {
      props: { isVip: true },
    })

    expect(wrapper.text()).toContain('VIP')
  })

  it('renders nothing when disabled', () => {
    const wrapper = mount(CustomerVipBadge, {
      props: { isVip: false },
    })

    expect(wrapper.text()).toBe('')
  })
})
