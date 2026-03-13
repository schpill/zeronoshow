import { beforeEach, describe, expect, it, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'

import RegisterPage from '@/pages/RegisterPage.vue'
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

describe('RegisterPage', () => {
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

  it('submits the registration payload and redirects on success', async () => {
    const pinia = createPinia()
    setActivePinia(pinia)
    const store = useAuthStore()
    store.register = vi.fn().mockResolvedValue(undefined)

    const wrapper = mount(RegisterPage, {
      global: { plugins: [pinia] },
    })

    await wrapper.get('#name').setValue('Gérald')
    await wrapper.get('#business_name').setValue('Le Bistrot')
    await wrapper.get('#email').setValue('owner@example.com')
    await wrapper.get('#phone').setValue('+33612345678')
    await wrapper.get('#password').setValue('secret123')
    await wrapper.get('#password_confirmation').setValue('secret123')
    await wrapper.get('form').trigger('submit.prevent')

    expect(store.register).toHaveBeenCalledWith({
      name: 'Gérald',
      business_name: 'Le Bistrot',
      email: 'owner@example.com',
      phone: '+33612345678',
      password: 'secret123',
      password_confirmation: 'secret123',
    })
    expect(push).toHaveBeenCalledWith('/dashboard')
  })

  it('shows field errors on 422', async () => {
    const pinia = createPinia()
    setActivePinia(pinia)
    const store = useAuthStore()
    store.register = vi.fn().mockRejectedValue({
      status: 422,
      data: { errors: { email: ['Email invalide'] } },
    })

    const wrapper = mount(RegisterPage, {
      global: { plugins: [pinia] },
    })

    await wrapper.get('form').trigger('submit.prevent')

    expect(wrapper.text()).toContain('Email invalide')
  })
})
