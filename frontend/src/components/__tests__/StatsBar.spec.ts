import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'

import StatsBar from '@/components/StatsBar.vue'

describe('StatsBar', () => {
  it('renders correct counts and status role', () => {
    const wrapper = mount(StatsBar, {
      props: {
        stats: {
          confirmed: 3,
          pending_verification: 1,
          pending_reminder: 2,
          cancelled: 4,
          no_show: 1,
          show: 8,
          total: 10,
        },
      },
    })

    expect(wrapper.attributes('role')).toBe('status')
    expect(wrapper.text()).toContain('Confirmées')
    expect(wrapper.text()).toContain('3')
    expect(wrapper.html()).toContain('text-emerald-700')
    expect(wrapper.html()).toContain('text-blue-700')
    expect(wrapper.html()).toContain('text-amber-700')
    expect(wrapper.html()).toContain('text-red-700')
  })
})
