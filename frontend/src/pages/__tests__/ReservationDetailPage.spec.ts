import { flushPromises, mount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'

import ReservationDetailPage from '@/pages/ReservationDetailPage.vue'

const updateStatus = vi.fn()

vi.mock('vue-router', () => ({
  useRoute: () => ({
    params: { id: 'res-1' },
  }),
}))

vi.mock('@/composables/useReservations', () => ({
  useReservations: () => ({
    fetchReservation: vi.fn().mockResolvedValue({
      reservation: {
        id: 'res-1',
        customer_name: 'Marc',
        status: 'confirmed',
        guests: 2,
        scheduled_at: '2026-03-13T19:00:00Z',
        phone_verified: true,
        reminder_2h_sent: false,
        reminder_30m_sent: false,
        status_changed_at: new Date().toISOString(),
      },
      customer: {
        id: 'cust-1',
        phone: '+33612345678',
        reliability_score: 90,
        score_tier: 'reliable',
        reservations_count: 2,
        shows_count: 2,
        no_shows_count: 0,
      },
      sms_logs: [
        {
          id: 'sms-1',
          type: 'reminder',
          status: 'delivered',
          phone: '+33612345678',
          body: 'Bonjour',
          cost_eur: 0.12,
        },
      ],
    }),
    updateStatus,
    loading: { updateStatus: { value: false } },
  }),
}))

describe('ReservationDetailPage', () => {
  it('renders reservation details and sms logs', async () => {
    const wrapper = mount(ReservationDetailPage, {
      global: {
        stubs: {
          AppLayout: { template: '<div><slot /></div>' },
        },
      },
    })

    await flushPromises()

    expect(wrapper.text()).toContain('Marc')
    expect(wrapper.text()).toContain('+33612345678')
    expect(wrapper.text()).toContain('reminder')
    expect(wrapper.text()).toContain('Présent')
    expect(wrapper.text()).toContain('No-show')
  })
})
