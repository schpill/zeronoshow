import { beforeEach, describe, expect, it, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'

import AppLayout from '@/layouts/AppLayout.vue'
import { useAuthStore } from '@/stores/auth'

describe('AppLayout', () => {
  beforeEach(() => {
    vi.stubGlobal('localStorage', {
      getItem: vi.fn(() => null),
      setItem: vi.fn(),
      removeItem: vi.fn(),
      clear: vi.fn(),
    })
    setActivePinia(createPinia())
  })

  it('shows an expiry banner when the trial is over', () => {
    const pinia = createPinia()
    setActivePinia(pinia)
    const store = useAuthStore()
    store.user = {
      id: 'biz-1',
      name: 'Le Bistrot',
      email: 'owner@example.com',
      subscription_status: 'trial',
      trial_ends_at: '2026-03-01T00:00:00Z',
    }

    const wrapper = mount(AppLayout, {
      global: {
        plugins: [pinia],
        stubs: {
          NavBar: true,
          RouterLink: {
            props: ['to'],
            template: '<a :href="to"><slot /></a>',
          },
        },
      },
      slots: {
        default: '<div>Dashboard</div>',
      },
    })

    expect(wrapper.text()).toContain('Essai gratuit expiré')
  })
})
