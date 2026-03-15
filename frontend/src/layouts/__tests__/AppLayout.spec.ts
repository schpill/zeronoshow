import { beforeEach, describe, expect, it, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'

import AppLayout from '@/layouts/AppLayout.vue'
import { useAuthStore } from '@/stores/auth'

describe('AppLayout', () => {
  beforeEach(() => {
    const sessionStorageMap = new Map<string, string>()
    vi.stubGlobal('localStorage', {
      getItem: vi.fn(() => null),
      setItem: vi.fn(),
      removeItem: vi.fn(),
      clear: vi.fn(),
    })
    vi.stubGlobal('sessionStorage', {
      getItem: vi.fn((key: string) => sessionStorageMap.get(key) ?? null),
      setItem: vi.fn((key: string, value: string) => {
        sessionStorageMap.set(key, value)
      }),
      removeItem: vi.fn((key: string) => {
        sessionStorageMap.delete(key)
      }),
      clear: vi.fn(() => {
        sessionStorageMap.clear()
      }),
    })
    window.history.replaceState({}, '', '/dashboard')
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
      onboarding_completed_at: null,
    }

    const wrapper = mount(AppLayout, {
      global: {
        plugins: [pinia],
        stubs: {
          NavBar: true,
          ToastContainer: true,
          RouterView: true,
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

  it('captures impersonation token from the url into session storage', async () => {
    const pinia = createPinia()
    setActivePinia(pinia)
    const store = useAuthStore()

    window.history.replaceState({}, '', '/dashboard?impersonation_token=imp-123')

    mount(AppLayout, {
      global: {
        plugins: [pinia],
        stubs: {
          NavBar: true,
          ToastContainer: true,
          RouterView: true,
          RouterLink: {
            props: ['to'],
            template: '<a :href="to"><slot /></a>',
          },
        },
      },
    })

    await Promise.resolve()

    expect(store.token).toBe('imp-123')
    expect(sessionStorage.getItem('znz_impersonation_token')).toBe('imp-123')
    expect(window.location.search).toBe('')
  })
})
