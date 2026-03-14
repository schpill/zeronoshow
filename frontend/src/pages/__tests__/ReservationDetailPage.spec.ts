import { flushPromises, mount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'

import ReservationDetailPage from '@/pages/ReservationDetailPage.vue'

const voiceApi = vi.hoisted(() => ({
  getVoiceCallLogs: vi.fn(),
  initiateVoiceCall: vi.fn(),
}))

const updateStatus = vi.fn()
const fetchReservation = vi.fn()

vi.mock('vue-router', () => ({
  useRoute: () => ({
    params: { id: 'res-1' },
  }),
}))

vi.mock('@/composables/useReservations', () => ({
  useReservations: () => ({
    fetchReservation,
    updateStatus,
    loading: { show: { value: false }, updateStatus: { value: false } },
  }),
}))

vi.mock('@/api/voiceCalls', () => ({
  getVoiceCallLogs: voiceApi.getVoiceCallLogs,
  initiateVoiceCall: voiceApi.initiateVoiceCall,
}))

vi.mock('@/composables/useToast', () => ({
  useToast: () => ({
    success: vi.fn(),
    warning: vi.fn(),
    error: vi.fn(),
    dismiss: vi.fn(),
    toasts: [],
  }),
}))

function makeReservationResponse() {
  return {
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
  }
}

describe('ReservationDetailPage', () => {
  it('renders reservation details and sms logs', async () => {
    fetchReservation.mockResolvedValue(makeReservationResponse())
    voiceApi.getVoiceCallLogs.mockResolvedValue([])

    const wrapper = mount(ReservationDetailPage, {
      global: {
        stubs: {
          AppLayout: { template: '<div><slot /></div>' },
          ErrorMessage: {
            props: ['message'],
            template: '<div data-test="detail-error">{{ message }}</div>',
          },
          LoadingSpinner: {
            template: '<div data-test="detail-spinner">loading</div>',
          },
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

  it('shows an error state when the reservation cannot be loaded', async () => {
    fetchReservation.mockRejectedValue(new Error('API down'))
    voiceApi.getVoiceCallLogs.mockResolvedValue([])

    const wrapper = mount(ReservationDetailPage, {
      global: {
        stubs: {
          AppLayout: { template: '<div><slot /></div>' },
          ErrorMessage: {
            props: ['message'],
            template: '<div data-test="detail-error">{{ message }}</div>',
          },
        },
      },
    })

    await flushPromises()

    expect(wrapper.get('[data-test="detail-error"]').text()).toContain('API down')
  })
})
