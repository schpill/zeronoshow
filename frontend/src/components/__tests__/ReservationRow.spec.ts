import { beforeEach, describe, expect, it, vi } from 'vitest'
import { mount } from '@vue/test-utils'

import ReservationRow from '@/components/ReservationRow.vue'
import type { ReservationRecord } from '@/types/reservations'

const updateStatus = vi.fn()

vi.mock('@/composables/useReservations', () => ({
  useReservations: () => ({
    updateStatus,
    loading: {
      updateStatus: { value: false },
    },
    errors: {
      updateStatus: { value: null },
    },
  }),
}))

function makeReservation(overrides: Partial<ReservationRecord> = {}): ReservationRecord {
  return {
    id: 'res-1',
    customer_name: 'Marc Dubois',
    scheduled_at: '2026-03-13T20:00:00.000Z',
    guests: 2,
    notes: null,
    status: 'pending_reminder',
    phone_verified: false,
    reminder_2h_sent: false,
    reminder_30m_sent: false,
    token_expires_at: null,
    created_at: '2026-03-13T10:00:00.000Z',
    status_changed_at: null,
    customer: {
      id: 'cus-1',
      phone: '+33612345678',
      reliability_score: 75,
      score_tier: 'average',
      reservations_count: 3,
      shows_count: 2,
      no_shows_count: 1,
      opted_out: false,
    },
    sms_count: 0,
    ...overrides,
  }
}

describe('ReservationRow', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    vi.useFakeTimers()
    vi.setSystemTime(new Date('2026-03-13T20:10:00.000Z'))
  })

  it('renders customer details, time, guests, status and score', () => {
    const wrapper = mount(ReservationRow, {
      props: {
        reservation: makeReservation(),
      },
    })

    expect(wrapper.text()).toContain('Marc Dubois')
    expect(wrapper.text()).toContain('2 couverts')
    expect(wrapper.text()).toContain('Moyen 75%')
    expect(wrapper.text()).toContain('Confirmé (rappel à venir)')
  })

  it('calls updateStatus with show and emits updated reservation', async () => {
    const updatedReservation = makeReservation({ status: 'show' })
    updateStatus.mockResolvedValueOnce({
      reservation: updatedReservation,
      customer: updatedReservation.customer,
    })

    const wrapper = mount(ReservationRow, {
      props: {
        reservation: makeReservation(),
      },
    })

    await wrapper.get('[data-test="mark-show"]').trigger('click')

    expect(updateStatus).toHaveBeenCalledWith('res-1', 'show')
    expect(wrapper.emitted('updated')?.[0]?.[0]).toEqual(updatedReservation)
  })

  it('calls updateStatus with no_show', async () => {
    const updatedReservation = makeReservation({
      status: 'no_show',
      status_changed_at: '2026-03-13T20:05:00.000Z',
    })
    updateStatus.mockResolvedValueOnce({
      reservation: updatedReservation,
      customer: updatedReservation.customer,
    })

    const wrapper = mount(ReservationRow, {
      props: {
        reservation: makeReservation(),
      },
    })

    await wrapper.get('[data-test="mark-no-show"]').trigger('click')

    expect(updateStatus).toHaveBeenCalledWith('res-1', 'no_show')
  })

  it('shows the undo button within the thirty minute window', () => {
    const wrapper = mount(ReservationRow, {
      props: {
        reservation: makeReservation({
          status: 'no_show',
          status_changed_at: '2026-03-13T19:50:00.000Z',
        }),
      },
    })

    expect(wrapper.find('[data-test="undo-no-show"]').exists()).toBe(true)
  })

  it('hides the undo button after thirty minutes', () => {
    const wrapper = mount(ReservationRow, {
      props: {
        reservation: makeReservation({
          status: 'no_show',
          status_changed_at: '2026-03-13T19:20:00.000Z',
        }),
      },
    })

    expect(wrapper.find('[data-test="undo-no-show"]').exists()).toBe(false)
  })
})
