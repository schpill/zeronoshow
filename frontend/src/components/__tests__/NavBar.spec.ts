import { beforeEach, describe, expect, it, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'

import NavBar from '@/components/NavBar.vue'
import { useAuthStore } from '@/stores/auth'

describe('NavBar', () => {
  beforeEach(() => {
    vi.stubGlobal('localStorage', {
      getItem: vi.fn(() => null),
      setItem: vi.fn(),
      removeItem: vi.fn(),
      clear: vi.fn(),
    })
    setActivePinia(createPinia())
  })

  it('shows the french logout label in the mobile menu', async () => {
    const pinia = createPinia()
    setActivePinia(pinia)
    const store = useAuthStore()
    store.user = {
      id: 'biz-1',
      name: 'Le Bistrot',
      email: 'owner@example.com',
      subscription_status: 'trial',
      trial_ends_at: '2026-03-26T00:00:00Z',
      onboarding_completed_at: null,
    }

    const wrapper = mount(NavBar, {
      global: {
        plugins: [pinia],
        stubs: {
          RouterLink: {
            props: ['to'],
            template: '<a :href="to"><slot /></a>',
          },
        },
      },
    })

    await wrapper.get('button[aria-label="Ouvrir la navigation"]').trigger('click')

    expect(wrapper.text()).toContain('Déconnexion')
    expect(wrapper.text()).not.toContain('Logout')
  })
})
