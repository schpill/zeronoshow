import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'

import LeoUpgradeBanner from '@/components/leo/LeoUpgradeBanner.vue'

describe('LeoUpgradeBanner', () => {
  it('renders the leo upsell copy and emits activate on click', async () => {
    const wrapper = mount(LeoUpgradeBanner, {
      props: {
        loading: false,
      },
    })

    expect(wrapper.text()).toContain('Activez Léo')
    expect(wrapper.text()).toContain('9€/mois')

    await wrapper.get('button').trigger('click')

    expect(wrapper.emitted('activate')).toEqual([[]])
  })

  it('disables the button and shows the spinner label while loading', () => {
    const wrapper = mount(LeoUpgradeBanner, {
      props: {
        loading: true,
      },
    })

    expect(wrapper.get('button').attributes('disabled')).toBeDefined()
    expect(wrapper.get('[role="status"]').attributes('aria-label')).toBe(
      'Activation de Léo en cours',
    )
  })
})
