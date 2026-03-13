import { flushPromises, mount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'

import ReservationDetailPage from '@/pages/ReservationDetailPage.vue'

vi.mock('vue-router', () => ({
  useRoute: () => ({
    params: { id: 'res-1' },
  }),
}))

vi.mock('@/composables/useReservations', () => ({
  useReservations: () => ({
    fetchReservation: vi.fn().mockResolvedValue({
      reservation: { id: 'res-1', customer_name: 'Marc', status: 'confirmed', guests: 2 },
      customer: {
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
  })
})
