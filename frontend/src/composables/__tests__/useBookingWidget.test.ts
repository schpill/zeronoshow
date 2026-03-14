import { describe, it, expect, vi, beforeEach } from 'vitest'
import { useBookingWidget } from '../useBookingWidget'

describe('useBookingWidget', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('starts at date step', () => {
    const { currentStep } = useBookingWidget()
    expect(currentStep.value).toBe('date')
  })

  it('goTo changes step', () => {
    const { currentStep, goTo } = useBookingWidget()

    goTo('guest')
    expect(currentStep.value).toBe('guest')

    goTo('otp')
    expect(currentStep.value).toBe('otp')

    goTo('confirm')
    expect(currentStep.value).toBe('confirm')
  })

  it('reset clears all state', () => {
    const state = useBookingWidget()

    state.selectedDate.value = '2026-03-20'
    state.selectedTime.value = '19:30'
    state.guestDetails.value = {
      guest_name: 'Jean',
      guest_phone: '+33612345678',
      party_size: 2,
    }
    state.guestToken.value = 'gt-abc'
    state.currentStep.value = 'confirm'

    state.reset()

    expect(state.currentStep.value).toBe('date')
    expect(state.selectedDate.value).toBeNull()
    expect(state.selectedTime.value).toBeNull()
    expect(state.guestDetails.value).toBeNull()
    expect(state.guestToken.value).toBeNull()
  })

  it('guestToken stored after OTP verified', () => {
    const { guestToken, hasGuestToken } = useBookingWidget()

    expect(guestToken.value).toBeNull()
    expect(hasGuestToken.value).toBe(false)

    guestToken.value = 'gt-abc'

    expect(guestToken.value).toBe('gt-abc')
    expect(hasGuestToken.value).toBe(true)
  })
})
