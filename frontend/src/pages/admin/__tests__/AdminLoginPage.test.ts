import { beforeEach, describe, expect, it, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'

import AdminLoginPage from '@/pages/admin/AdminLoginPage.vue'
import { useAdminStore } from '@/stores/admin'

const push = vi.fn()

vi.mock('vue-router', async () => {
  const actual = await vi.importActual<typeof import('vue-router')>('vue-router')
  return { ...actual, useRouter: () => ({ push }) }
})

describe('AdminLoginPage', () => {
  beforeEach(() => {
    const storage = new Map<string, string>()
    vi.stubGlobal('localStorage', {
      getItem: vi.fn((key: string) => storage.get(key) ?? null),
      setItem: vi.fn((key: string, value: string) => storage.set(key, value)),
      removeItem: vi.fn((key: string) => storage.delete(key)),
      clear: vi.fn(() => storage.clear()),
    })
    setActivePinia(createPinia())
    push.mockReset()
  })

  it('form submits on enter', async () => {
    const pinia = createPinia()
    setActivePinia(pinia)
    const store = useAdminStore()
    store.login = vi.fn().mockResolvedValue(undefined)

    const wrapper = mount(AdminLoginPage, { global: { plugins: [pinia] } })

    await wrapper.get('#admin-email').setValue('admin@example.com')
    await wrapper.get('#admin-password').setValue('secret123')
    await wrapper.get('form').trigger('submit.prevent')

    expect(store.login).toHaveBeenCalledWith('admin@example.com', 'secret123')
  })

  it('401 shows error message', async () => {
    const pinia = createPinia()
    setActivePinia(pinia)
    const store = useAdminStore()
    store.login = vi.fn().mockRejectedValue({ status: 401 })

    const wrapper = mount(AdminLoginPage, { global: { plugins: [pinia] } })
    await wrapper.get('form').trigger('submit.prevent')

    expect(wrapper.text()).toContain('Identifiants invalides')
  })

  it('429 shows lockout message', async () => {
    const pinia = createPinia()
    setActivePinia(pinia)
    const store = useAdminStore()
    store.login = vi.fn().mockRejectedValue({ status: 429 })

    const wrapper = mount(AdminLoginPage, { global: { plugins: [pinia] } })
    await wrapper.get('form').trigger('submit.prevent')

    expect(wrapper.text()).toContain('Trop de tentatives, réessayez dans 15 minutes')
  })

  it('successful login redirects to /admin/dashboard', async () => {
    const pinia = createPinia()
    setActivePinia(pinia)
    const store = useAdminStore()
    store.login = vi.fn().mockResolvedValue(undefined)

    const wrapper = mount(AdminLoginPage, { global: { plugins: [pinia] } })
    await wrapper.get('form').trigger('submit.prevent')

    expect(push).toHaveBeenCalledWith('/admin/dashboard')
  })
})
