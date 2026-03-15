import { beforeEach, describe, expect, it, vi } from 'vitest'
import { mount } from '@vue/test-utils'

import { adminApiClient } from '@/api/adminAxios'
import AdminBusinessDetailPage from '@/pages/admin/AdminBusinessDetailPage.vue'

const route = { params: { id: 'biz-1' } }

vi.mock('vue-router', async () => {
  const actual = await vi.importActual<typeof import('vue-router')>('vue-router')
  return { ...actual, useRoute: () => route }
})

vi.mock('@/api/adminAxios', () => ({
  adminApiClient: {
    get: vi.fn(),
    patch: vi.fn(),
    post: vi.fn(),
  },
}))

describe('AdminBusinessDetailPage', () => {
  beforeEach(() => {
    vi.mocked(adminApiClient.get).mockReset()
    vi.mocked(adminApiClient.patch).mockReset()
    vi.mocked(adminApiClient.post).mockReset()
    vi.stubGlobal('open', vi.fn())
    vi.mocked(adminApiClient.get).mockResolvedValue({
      business: {
        id: 'biz-1',
        name: 'Alpha Bistro',
        email: 'alpha@example.com',
        phone: '+33123456789',
        subscription_status: 'trial',
        trial_ends_at: '2026-03-22T12:00:00Z',
        created_at: '2026-03-01T09:00:00Z',
      },
      recent_reservations: [
        {
          id: 'res-1',
          customer_name: 'Jane Doe',
          status: 'confirmed',
          scheduled_at: '2026-03-18T19:30:00Z',
        },
      ],
      sms_log_summary: {
        total_sent: 12,
        delivered: 10,
        failed: 2,
        cost: 4.25,
      },
      subscription_history: [
        {
          subscription_status: 'trial',
          trial_ends_at: '2026-03-22T12:00:00Z',
          stripe_customer_id: 'cus_123',
          stripe_subscription_id: 'sub_123',
        },
      ],
    })
  })

  it('renders business metadata and subscription history', async () => {
    const wrapper = mount(AdminBusinessDetailPage)

    await Promise.resolve()
    await Promise.resolve()

    expect(wrapper.text()).toContain('Alpha Bistro')
    expect(wrapper.text()).toContain('+33123456789')
    expect(wrapper.text()).toContain('trial')
    expect(wrapper.text()).toContain('cus_123')
  })

  it('requires confirmation before cancelling subscription', async () => {
    vi.mocked(adminApiClient.patch).mockResolvedValue({})

    const wrapper = mount(AdminBusinessDetailPage)
    await Promise.resolve()
    await Promise.resolve()

    await wrapper.get('textarea').setValue('Fraud risk')
    await wrapper.get('[data-testid="open-cancel-modal"]').trigger('click')

    expect(wrapper.text()).toContain("Confirmer l'annulation")
    expect(adminApiClient.patch).not.toHaveBeenCalled()

    await wrapper.get('[data-testid="confirm-cancel"]').trigger('click')

    expect(adminApiClient.patch).toHaveBeenCalledWith('/businesses/biz-1/cancel-subscription', {
      reason: 'Fraud risk',
    })
  })

  it('opens a new tab after impersonation confirmation', async () => {
    vi.mocked(adminApiClient.post).mockResolvedValue({ impersonation_token: 'imp-123' })

    const wrapper = mount(AdminBusinessDetailPage)
    await Promise.resolve()
    await Promise.resolve()

    await wrapper.get('[data-testid="open-impersonation-modal"]').trigger('click')
    await wrapper.get('[data-testid="confirm-impersonation"]').trigger('click')

    expect(adminApiClient.post).toHaveBeenCalledWith('/businesses/biz-1/impersonate')
    expect(window.open).toHaveBeenCalledWith(
      '/dashboard?impersonation_token=imp-123',
      '_blank',
      'noopener',
    )
  })
})
