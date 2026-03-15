import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'

import StatCard from '@/components/admin/StatCard.vue'

describe('StatCard', () => {
  it('renders label and value', () => {
    const wrapper = mount(StatCard, {
      props: {
        label: 'Total businesses',
        value: 42,
      },
    })

    expect(wrapper.text()).toContain('Total businesses')
    expect(wrapper.text()).toContain('42')
  })

  it('correct color class applied', () => {
    const wrapper = mount(StatCard, {
      props: {
        label: 'Active trials',
        value: 5,
        color: 'green',
      },
    })

    expect(wrapper.html()).toContain('bg-emerald-100')
  })
})
