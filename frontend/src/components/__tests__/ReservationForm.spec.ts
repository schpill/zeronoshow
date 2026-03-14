import { beforeEach, describe, expect, it, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { nextTick } from 'vue'

import ReservationForm from '@/components/ReservationForm.vue'

const createReservation = vi.fn()
const lookupCustomer = vi.fn()

vi.mock('@/composables/useReservations', () => ({
  useReservations: () => ({
    createReservation,
    lookupCustomer,
    loading: {
      create: { value: false },
      lookup: { value: false },
    },
    errors: {
      create: { value: null },
      lookup: { value: null },
    },
  }),
}))

describe('ReservationForm', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('submits form data to createReservation', async () => {
    createReservation.mockResolvedValueOnce({ reservation: { id: 'res-1' } })

    const wrapper = mount(ReservationForm)

    await wrapper.get('#customer_name').setValue('Marc Dubois')
    await wrapper.get('#phone').setValue('+33612345678')
    await wrapper.get('#scheduled_at').setValue('2026-03-12T20:00')
    await wrapper.get('#guests').setValue('2')
    await wrapper.get('form').trigger('submit.prevent')

    expect(createReservation).toHaveBeenCalledWith({
      customer_name: 'Marc Dubois',
      phone: '+33612345678',
      scheduled_at: '2026-03-12T20:00',
      guests: 2,
      notes: '',
      phone_verified: false,
    })
    expect(wrapper.emitted('created')?.[0]?.[0]).toEqual({ id: 'res-1' })
  })

  it('shows inline errors for each field on 422', async () => {
    createReservation.mockRejectedValueOnce({
      status: 422,
      data: {
        errors: {
          customer_name: ['Le nom est requis'],
          phone: ['Le téléphone est invalide'],
        },
      },
    })

    const wrapper = mount(ReservationForm)

    await wrapper.get('form').trigger('submit.prevent')
    await nextTick()

    expect(wrapper.text()).toContain('Le nom est requis')
    expect(wrapper.text()).toContain('Le téléphone est invalide')
  })

  it('calls lookupCustomer on phone blur and shows badge', async () => {
    lookupCustomer.mockResolvedValueOnce({
      found: true,
      reliability_score: 94,
      score_tier: 'reliable',
      is_blacklisted: false,
    })

    const wrapper = mount(ReservationForm)

    await wrapper.get('#phone').setValue('+33612345678')
    await wrapper.get('#phone').trigger('blur')
    await nextTick()

    expect(lookupCustomer).toHaveBeenCalledWith('+33612345678')
    expect(wrapper.text()).toContain('Fiable 94%')
  })

  it('shows a blacklist warning when the looked up customer is blacklisted', async () => {
    lookupCustomer.mockResolvedValueOnce({
      found: true,
      reliability_score: 42,
      score_tier: 'at_risk',
      is_blacklisted: true,
    })

    const wrapper = mount(ReservationForm)

    await wrapper.get('#phone').setValue('+33612345678')
    await wrapper.get('#phone').trigger('blur')
    await nextTick()

    expect(wrapper.text()).toContain('liste noire')
  })

  it('toggles phone_verified in payload', async () => {
    createReservation.mockResolvedValueOnce({ reservation: { id: 'res-1' } })

    const wrapper = mount(ReservationForm)
    await wrapper.get('#customer_name').setValue('Marc Dubois')
    await wrapper.get('#phone').setValue('+33612345678')
    await wrapper.get('#scheduled_at').setValue('2026-03-12T20:00')
    await wrapper.get('#phone_verified').setValue(true)
    await wrapper.get('form').trigger('submit.prevent')

    expect(createReservation).toHaveBeenCalledWith(
      expect.objectContaining({ phone_verified: true }),
    )
  })

  it('resets form after successful submission', async () => {
    createReservation.mockResolvedValueOnce({ reservation: { id: 'res-1' } })

    const wrapper = mount(ReservationForm)
    await wrapper.get('#customer_name').setValue('Marc Dubois')
    await wrapper.get('#phone').setValue('+33612345678')
    await wrapper.get('#scheduled_at').setValue('2026-03-12T20:00')
    await wrapper.get('form').trigger('submit.prevent')

    expect((wrapper.get('#customer_name').element as HTMLInputElement).value).toBe('')
    expect((wrapper.get('#phone').element as HTMLInputElement).value).toBe('')
  })
})
