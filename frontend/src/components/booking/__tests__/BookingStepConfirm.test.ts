import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import BookingStepConfirm from '../BookingStepConfirm.vue'

vi.mock('@/api/widget', () => ({
  createReservation: vi.fn(),
}))

import { createReservation } from '@/api/widget'

describe('BookingStepConfirm', () => {
  const defaultProps = {
    businessToken: 'tok-123',
    selectedDate: '2026-03-20',
    selectedTime: '19:30',
    guestDetails: {
      guest_name: 'Jean Dupont',
      guest_phone: '+33612345678',
      party_size: 4,
    },
    guestToken: 'gt-abc',
    accentColour: '#6366f1',
  }

  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('renders reservation summary correctly', () => {
    const wrapper = mount(BookingStepConfirm, { props: defaultProps })

    expect(wrapper.text()).toContain('Confirmer la réservation')
    expect(wrapper.text()).toContain('2026-03-20')
    expect(wrapper.text()).toContain('19:30')
    expect(wrapper.text()).toContain('Jean Dupont')
    expect(wrapper.text()).toContain('4')
  })

  it('calls createReservation on confirm click', async () => {
    vi.mocked(createReservation).mockResolvedValue({ reservation: { id: 'r-1' } })

    const wrapper = mount(BookingStepConfirm, { props: defaultProps })

    const confirmBtn = wrapper.find('button[type="button"]')
    await confirmBtn.trigger('click')

    expect(createReservation).toHaveBeenCalledWith('tok-123', {
      guest_token: 'gt-abc',
      party_size: 4,
      date: '2026-03-20',
      time: '19:30',
      guest_name: 'Jean Dupont',
      guest_phone: '+33612345678',
    })

    expect(wrapper.emitted('confirmed')).toBeTruthy()
  })

  it('shows conflict error on 409', async () => {
    vi.mocked(createReservation).mockRejectedValue({
      status: 409,
      data: { error: { message: 'Créneau indisponible.' } },
    })

    const wrapper = mount(BookingStepConfirm, { props: defaultProps })

    const confirmBtn = wrapper.find('button[type="button"]')
    await confirmBtn.trigger('click')
    await wrapper.vm.$nextTick()

    expect(wrapper.emitted('conflict')).toBeTruthy()
    expect(wrapper.emitted('confirmed')).toBeFalsy()
  })
})
