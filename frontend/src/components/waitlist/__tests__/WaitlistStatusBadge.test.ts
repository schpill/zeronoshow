import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import { nextTick } from 'vue'
import WaitlistStatusBadge from '../WaitlistStatusBadge.vue'

describe('WaitlistStatusBadge', () => {
  it('renders correct label and color for pending status', () => {
    const wrapper = mount(WaitlistStatusBadge, {
      props: {
        status: 'pending',
        label: 'En attente',
      },
    })
    expect(wrapper.text()).toContain('En attente')
    expect(wrapper.find('span').classes()).toContain('bg-gray-100')
  })

  it('renders notified status with countdown', async () => {
    const expiresAt = new Date(Date.now() + 5 * 60000).toISOString()
    const wrapper = mount(WaitlistStatusBadge, {
      props: {
        status: 'notified',
        label: 'Notifié',
        expiresAt,
      },
    })
    await nextTick()
    expect(wrapper.text()).toContain('Notifié')
    expect(wrapper.text()).toContain('4:') // 4 minutes and something
  })
})
