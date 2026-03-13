import { mount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'

import Dashboard from '@/pages/Dashboard.vue'

const fetchDashboard = vi.fn().mockResolvedValue({
  reservations: [
    {
      id: 'res-1',
      customer_name: 'Marc',
      status: 'confirmed',
      scheduled_at: '2026-03-13T19:00:00Z',
      guests: 2,
      notes: null,
      phone_verified: true,
      reminder_2h_sent: false,
      reminder_30m_sent: false,
    },
    {
      id: 'res-2',
      customer_name: 'Lina',
      status: 'no_show',
      scheduled_at: '2026-03-14T19:00:00Z',
      guests: 4,
      notes: null,
      phone_verified: true,
      reminder_2h_sent: false,
      reminder_30m_sent: false,
    },
  ],
  stats: {
    confirmed: 1,
    pending_verification: 0,
    pending_reminder: 0,
    cancelled: 0,
    no_show: 0,
    show: 0,
    total: 1,
  },
  sms_cost_this_month: 1.2,
  weekly_no_show_rate: 0,
})

vi.mock('@/composables/useReservations', () => ({
  useReservations: () => ({
    fetchDashboard,
    loading: { fetch: { value: false }, updateStatus: { value: false } },
    updateStatus: vi.fn(),
  }),
}))

vi.mock('@/composables/usePolling', () => ({
  usePolling: (callback: () => Promise<void>) => {
    void callback()
  },
}))

describe('Dashboard', () => {
  it('renders stats and refreshes when switching to weekly view', async () => {
    const wrapper = mount(Dashboard, {
      global: {
        stubs: {
          AppLayout: { template: '<div><slot /></div>' },
          ReservationForm: {
            template:
              '<button @click="$emit(`created`, { id: `res-2`, customer_name: `Lina`, status: `confirmed`, scheduled_at: `2026-03-13T21:00:00Z`, guests: 2, notes: null, phone_verified: true, reminder_2h_sent: false, reminder_30m_sent: false })">create</button>',
          },
          ReservationList: {
            props: ['reservations'],
            template: '<div>{{ reservations.length }}</div>',
          },
          ReservationRow: {
            props: ['reservation'],
            template: '<article>{{ reservation.customer_name }}</article>',
          },
        },
      },
    })

    await Promise.resolve()

    expect(wrapper.text()).toContain('Réservations')
    expect(wrapper.text()).toContain('1.20 € SMS')

    await wrapper.get('button:nth-of-type(2)').trigger('click')

    expect(fetchDashboard).toHaveBeenCalled()
    expect(wrapper.text()).toContain('mars')
  })
})
