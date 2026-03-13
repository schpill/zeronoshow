import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'

import * as api from '@/api/axios'
import { useAuthStore } from '@/stores/auth'

vi.mock('@/api/axios', () => ({
  apiClient: {
    post: vi.fn(),
  },
}))

describe('auth store', () => {
  beforeEach(() => {
    const storage = new Map<string, string>()
    vi.stubGlobal('localStorage', {
      getItem: vi.fn((key: string) => storage.get(key) ?? null),
      setItem: vi.fn((key: string, value: string) => {
        storage.set(key, value)
      }),
      removeItem: vi.fn((key: string) => {
        storage.delete(key)
      }),
      clear: vi.fn(() => {
        storage.clear()
      }),
    })
    localStorage.clear()
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('stores token in localStorage on login', async () => {
    vi.mocked(api.apiClient.post).mockResolvedValueOnce({
      token: 'token-123',
      business: {
        id: 'biz-1',
        name: 'Le Bistrot',
        subscription_status: 'trial',
        trial_ends_at: '2026-03-26T00:00:00Z',
      },
    })

    const store = useAuthStore()

    await store.login('owner@example.com', 'password123')

    expect(localStorage.getItem('znz_token')).toBe('token-123')
    expect(store.token).toBe('token-123')
    expect(store.user?.name).toBe('Le Bistrot')
  })

  it('clears localStorage and state on logout', async () => {
    vi.mocked(api.apiClient.post).mockResolvedValueOnce(undefined)

    const store = useAuthStore()
    store.token = 'token-123'
    store.user = {
      id: 'biz-1',
      name: 'Le Bistrot',
      email: 'owner@example.com',
      subscription_status: 'trial',
      trial_ends_at: '2026-03-26T00:00:00Z',
    }
    localStorage.setItem('znz_token', 'token-123')

    await store.logout()

    expect(localStorage.getItem('znz_token')).toBeNull()
    expect(store.token).toBeNull()
    expect(store.user).toBeNull()
  })

  it('returns false when token is null', () => {
    const store = useAuthStore()

    expect(store.isAuthenticated).toBe(false)
  })

  it('returns false when trial is expired', () => {
    const store = useAuthStore()
    store.user = {
      id: 'biz-1',
      name: 'Le Bistrot',
      email: 'owner@example.com',
      subscription_status: 'trial',
      trial_ends_at: '2026-03-01T00:00:00Z',
    }

    expect(store.isOnActivePlan).toBe(false)
  })
})
