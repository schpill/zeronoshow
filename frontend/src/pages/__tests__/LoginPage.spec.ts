import { beforeEach, describe, expect, it, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'

import LoginPage from '@/pages/LoginPage.vue'
import { useAuthStore } from '@/stores/auth'

const push = vi.fn()

vi.mock('vue-router', async () => {
  const actual = await vi.importActual<typeof import('vue-router')>('vue-router')

  return {
    ...actual,
    useRouter: () => ({ push }),
    RouterLink: {
      props: ['to'],
      template: '<a :href="to"><slot /></a>',
    },
  }
})

describe('LoginPage', () => {
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
    setActivePinia(createPinia())
    push.mockReset()
  })

  it('submits email and password on form submit', async () => {
    const pinia = createPinia()
    setActivePinia(pinia)
    const store = useAuthStore()
    store.login = vi.fn().mockResolvedValue(undefined)

    const wrapper = mount(LoginPage, {
      global: { plugins: [pinia] },
    })

    await wrapper.get('#email').setValue('owner@example.com')
    await wrapper.get('#password').setValue('secret123')
    await wrapper.get('form').trigger('submit.prevent')

    expect(store.login).toHaveBeenCalledWith('owner@example.com', 'secret123')
    expect(push).toHaveBeenCalledWith('/dashboard')
  })

  it('shows 401 error message', async () => {
    const pinia = createPinia()
    setActivePinia(pinia)
    const store = useAuthStore()
    store.login = vi.fn().mockRejectedValue({ status: 401 })

    const wrapper = mount(LoginPage, {
      global: { plugins: [pinia] },
    })

    await wrapper.get('form').trigger('submit.prevent')

    expect(wrapper.text()).toContain('Email ou mot de passe incorrect.')
  })

  it('shows field errors on 422', async () => {
    const pinia = createPinia()
    setActivePinia(pinia)
    const store = useAuthStore()
    store.login = vi.fn().mockRejectedValue({
      status: 422,
      data: { errors: { email: ['Email invalide'] } },
    })

    const wrapper = mount(LoginPage, {
      global: { plugins: [pinia] },
    })

    await wrapper.get('form').trigger('submit.prevent')

    expect(wrapper.text()).toContain('Email invalide')
  })
})
