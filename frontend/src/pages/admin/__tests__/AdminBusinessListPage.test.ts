import { beforeEach, describe, expect, it, vi } from 'vitest'
import { mount } from '@vue/test-utils'

import { adminApiClient } from '@/api/adminAxios'
import AdminBusinessListPage from '@/pages/admin/AdminBusinessListPage.vue'

const push = vi.fn()

vi.mock('vue-router', async () => {
  const actual = await vi.importActual<typeof import('vue-router')>('vue-router')
  return { ...actual, useRouter: () => ({ push }) }
})

vi.mock('@/api/adminAxios', () => ({
  adminApiClient: {
    get: vi.fn(),
  },
}))

describe('AdminBusinessListPage', () => {
  beforeEach(() => {
    vi.useFakeTimers()
    push.mockReset()
    vi.mocked(adminApiClient.get).mockReset()
    vi.mocked(adminApiClient.get).mockResolvedValue({
      data: [
        {
          id: 'biz-1',
          name: 'Alpha Bistro',
          email: 'alpha@example.com',
          subscription_status: 'trial',
          trial_ends_at: null,
          reservations_count: 12,
          sms_sent_count: 40,
          created_at: '2026-03-15T12:00:00Z',
        },
      ],
      meta: { total: 1 },
    })
  })

  it('renders table with business rows', async () => {
    const wrapper = mount(AdminBusinessListPage, {
      global: {
        stubs: {
          RouterView: true,
          RouterLink: { props: ['to'], template: '<a :href="to"><slot /></a>' },
        },
      },
    })

    await vi.runAllTimersAsync()
    await Promise.resolve()

    expect(wrapper.text()).toContain('Alpha Bistro')
  })

  it('search debounce calls API after 300ms', async () => {
    const wrapper = mount(AdminBusinessListPage)
    await vi.runAllTimersAsync()
    await Promise.resolve()

    await wrapper.get('input[type="search"]').setValue('alpha')
    expect(adminApiClient.get).toHaveBeenCalledTimes(1)

    vi.advanceTimersByTime(300)
    await Promise.resolve()

    expect(adminApiClient.get).toHaveBeenCalledTimes(2)
    expect(vi.mocked(adminApiClient.get).mock.calls[1]?.[0]).toContain('search=alpha')
  })

  it('status filter emits correct API param', async () => {
    const wrapper = mount(AdminBusinessListPage)
    await vi.runAllTimersAsync()
    await Promise.resolve()

    await wrapper.get('select').setValue('active')
    vi.advanceTimersByTime(300)
    await Promise.resolve()

    expect(vi.mocked(adminApiClient.get).mock.calls[1]?.[0]).toContain('status=active')
  })

  it('row click navigates to detail', async () => {
    const wrapper = mount(AdminBusinessListPage)
    await vi.runAllTimersAsync()
    await Promise.resolve()

    await wrapper.get('tbody tr').trigger('click')

    expect(push).toHaveBeenCalledWith('/admin/businesses/biz-1')
  })
})
