import { mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'

import TrialBanner from '@/components/TrialBanner.vue'

describe('TrialBanner', () => {
  beforeEach(() => {
    vi.stubGlobal('sessionStorage', {
      getItem: vi.fn(() => null),
      setItem: vi.fn(),
    })
  })

  it('shows for trials ending soon and links to subscription', () => {
    const wrapper = mount(TrialBanner, {
      props: {
        subscriptionStatus: 'trial',
        trialEndsAt: new Date(Date.now() + 2 * 86_400_000).toISOString(),
      },
      global: {
        stubs: {
          RouterLink: {
            props: ['to'],
            template: '<a :href="to"><slot /></a>',
          },
        },
      },
    })

    expect(wrapper.text()).toContain('Votre essai expire')
    expect(wrapper.html()).toContain('/subscription')
    expect(wrapper.html()).toContain('border-red-200')
  })

  it('is hidden for active subscriptions', () => {
    const wrapper = mount(TrialBanner, {
      props: {
        subscriptionStatus: 'active',
        trialEndsAt: new Date(Date.now() + 2 * 86_400_000).toISOString(),
      },
      global: {
        stubs: ['RouterLink'],
      },
    })

    expect(wrapper.text()).toBe('')
  })
})
