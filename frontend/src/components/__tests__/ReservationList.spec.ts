import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'

import ReservationList from '@/components/ReservationList.vue'
import type { ReservationRecord } from '@/types/reservations'

function makeReservation(id: string, scheduledAt: string): ReservationRecord {
  return {
    id,
    customer_name: `Client ${id}`,
    scheduled_at: scheduledAt,
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
      id: `cus-${id}`,
      phone: '+33612345678',
      reliability_score: 80,
      score_tier: 'average',
      reservations_count: 3,
      shows_count: 2,
      no_shows_count: 1,
      opted_out: false,
    },
    sms_count: 0,
  }
}

describe('ReservationList', () => {
  it('renders rows sorted by scheduled_at', () => {
    const wrapper = mount(ReservationList, {
      props: {
        reservations: [
          makeReservation('b', '2026-03-13T20:00:00.000Z'),
          makeReservation('a', '2026-03-13T18:00:00.000Z'),
        ],
        loading: false,
      },
    })

    const rows = wrapper.findAll('[data-test="reservation-row"]')
    expect(rows).toHaveLength(2)
    expect(rows[0]?.text()).toContain('Client a')
    expect(rows[1]?.text()).toContain('Client b')
  })

  it('shows skeletons while loading', () => {
    const wrapper = mount(ReservationList, {
      props: {
        reservations: [],
        loading: true,
      },
    })

    expect(wrapper.findAll('[data-test="reservation-skeleton"]')).toHaveLength(3)
  })

  it('shows the empty state when there are no reservations', () => {
    const wrapper = mount(ReservationList, {
      props: {
        reservations: [],
        loading: false,
      },
    })

    expect(wrapper.text()).toContain('Aucune réservation pour cette journée.')
  })

  it('updates the row when a child emits updated', async () => {
    const wrapper = mount(ReservationList, {
      props: {
        reservations: [makeReservation('a', '2026-03-13T18:00:00.000Z')],
        loading: false,
      },
    })

    await wrapper
      .getComponent({ name: 'ReservationRow' })
      .vm.$emit('updated', makeReservation('a', '2026-03-13T18:00:00.000Z'))

    const emitted = wrapper.emitted('updated')
    expect(Array.isArray(emitted)).toBe(true)
    expect((emitted?.[0]?.[0] as ReservationRecord | undefined)?.id).toBe('a')
  })
})
