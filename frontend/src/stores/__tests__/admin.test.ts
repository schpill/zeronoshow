import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'

import { adminApiClient } from '@/api/adminAxios'
import { useAdminStore } from '@/stores/admin'

vi.mock('@/api/adminAxios', () => ({
  adminApiClient: {
    post: vi.fn(),
  },
}))

describe('admin store', () => {
  beforeEach(() => {
    const storage = new Map<string, string>()
    vi.stubGlobal('localStorage', {
      getItem: vi.fn((key: string) => storage.get(key) ?? null),
      setItem: vi.fn((key: string, value: string) => storage.set(key, value)),
      removeItem: vi.fn((key: string) => storage.delete(key)),
      clear: vi.fn(() => storage.clear()),
    })
    setActivePinia(createPinia())
    vi.mocked(adminApiClient.post).mockReset()
  })

  it('login stores token', async () => {
    vi.mocked(adminApiClient.post).mockResolvedValue({
      token: 'admin-token',
      admin: { id: '1', name: 'Gerald', email: 'gerald@example.com' },
    })

    const store = useAdminStore()
    await store.login('gerald@example.com', 'secret')

    expect(store.token).toBe('admin-token')
    expect(localStorage.setItem).toHaveBeenCalledWith('znz_admin_token', 'admin-token')
  })

  it('logout clears state and localStorage', async () => {
    vi.mocked(adminApiClient.post).mockResolvedValue(undefined)

    const store = useAdminStore()
    store.token = 'admin-token'
    store.admin = { id: '1', name: 'Gerald', email: 'gerald@example.com' }

    await store.logout()

    expect(store.token).toBeNull()
    expect(store.admin).toBeNull()
    expect(localStorage.removeItem).toHaveBeenCalledWith('znz_admin_token')
  })

  it('isAuthenticated true after login', async () => {
    vi.mocked(adminApiClient.post).mockResolvedValue({
      token: 'admin-token',
      admin: { id: '1', name: 'Gerald', email: 'gerald@example.com' },
    })

    const store = useAdminStore()
    await store.login('gerald@example.com', 'secret')

    expect(store.isAuthenticated).toBe(true)
  })

  it('isAuthenticated false after logout', async () => {
    vi.mocked(adminApiClient.post).mockResolvedValue(undefined)

    const store = useAdminStore()
    store.token = 'admin-token'

    await store.logout()

    expect(store.isAuthenticated).toBe(false)
  })
})
