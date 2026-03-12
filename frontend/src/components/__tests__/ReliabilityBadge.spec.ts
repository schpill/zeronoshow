import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'

import ReliabilityBadge from '@/components/ReliabilityBadge.vue'

describe('ReliabilityBadge', () => {
  it('renders green pill for reliable score', () => {
    const wrapper = mount(ReliabilityBadge, {
      props: { score: 94, tier: 'reliable' },
    })

    expect(wrapper.text()).toContain('Fiable 94%')
    expect(wrapper.attributes('aria-label')).toBe('Reliability score: Fiable 94%')
    expect(wrapper.classes()).toContain('bg-emerald-100')
  })

  it('renders orange pill for average score', () => {
    const wrapper = mount(ReliabilityBadge, {
      props: { score: 75, tier: 'average' },
    })

    expect(wrapper.text()).toContain('Moyen 75%')
    expect(wrapper.classes()).toContain('bg-amber-100')
  })

  it('renders red pill for at risk score', () => {
    const wrapper = mount(ReliabilityBadge, {
      props: { score: 58, tier: 'at_risk' },
    })

    expect(wrapper.text()).toContain('À risque 58%')
    expect(wrapper.classes()).toContain('bg-red-100')
  })

  it('renders grey pill for null score', () => {
    const wrapper = mount(ReliabilityBadge, {
      props: { score: null, tier: null },
    })

    expect(wrapper.text()).toContain('Inconnu')
    expect(wrapper.classes()).toContain('bg-slate-100')
  })
})
