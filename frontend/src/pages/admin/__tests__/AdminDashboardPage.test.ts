import { describe, expect, it, vi } from 'vitest'
import { mount } from '@vue/test-utils'

import { adminApiClient } from '@/api/adminAxios'
import AdminDashboardPage from '@/pages/admin/AdminDashboardPage.vue'

vi.mock('@/api/adminAxios', () => ({
  adminApiClient: {
    get: vi.fn(),
  },
}))

describe('AdminDashboardPage', () => {
  it('renders 6 stat cards', async () => {
    vi.mocked(adminApiClient.get)
      .mockResolvedValueOnce({
        total_businesses: 10,
        active_trials: 3,
        expired_trials: 1,
        paid_subscriptions: 5,
        cancelled_subscriptions: 1,
        sms_sent_this_month: 400,
        sms_cost_this_month: 12.4,
        failed_jobs_count: 0,
      })
      .mockResolvedValueOnce({
        queue_worker_running: true,
        failed_jobs_count: 0,
        redis_ping: true,
        last_twilio_webhook_at: '2026-03-15T12:00:00Z',
        database_ok: true,
      })

    const wrapper = mount(AdminDashboardPage, {
      global: {
        stubs: {
          RouterView: true,
          RouterLink: { props: ['to'], template: '<a :href="to"><slot /></a>' },
        },
      },
    })

    await Promise.resolve()
    await Promise.resolve()

    expect(wrapper.findAllComponents({ name: 'StatCard' })).toHaveLength(6)
  })

  it('health panel shows all 4 indicators', async () => {
    vi.mocked(adminApiClient.get)
      .mockResolvedValueOnce({
        total_businesses: 10,
        active_trials: 3,
        expired_trials: 1,
        paid_subscriptions: 5,
        cancelled_subscriptions: 1,
        sms_sent_this_month: 400,
        sms_cost_this_month: 12.4,
        failed_jobs_count: 0,
      })
      .mockResolvedValueOnce({
        queue_worker_running: true,
        failed_jobs_count: 0,
        redis_ping: true,
        last_twilio_webhook_at: '2026-03-15T12:00:00Z',
        database_ok: true,
      })

    const wrapper = mount(AdminDashboardPage)
    await Promise.resolve()
    await Promise.resolve()

    expect(wrapper.findAllComponents({ name: 'HealthIndicator' })).toHaveLength(4)
  })

  it('green dot for queue healthy, red dot when failed jobs > 0', async () => {
    vi.mocked(adminApiClient.get)
      .mockResolvedValueOnce({
        total_businesses: 10,
        active_trials: 3,
        expired_trials: 1,
        paid_subscriptions: 5,
        cancelled_subscriptions: 1,
        sms_sent_this_month: 400,
        sms_cost_this_month: 12.4,
        failed_jobs_count: 1,
      })
      .mockResolvedValueOnce({
        queue_worker_running: true,
        failed_jobs_count: 2,
        redis_ping: true,
        last_twilio_webhook_at: '2026-03-15T12:00:00Z',
        database_ok: true,
      })

    const wrapper = mount(AdminDashboardPage)
    await Promise.resolve()
    await Promise.resolve()

    expect(wrapper.html()).toContain('bg-emerald-500')
    expect(wrapper.html()).toContain('bg-red-500')
  })
})
