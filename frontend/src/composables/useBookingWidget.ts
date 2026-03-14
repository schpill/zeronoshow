import { ref, computed } from 'vue'
import type { WidgetConfig } from '@/api/widget'

export type BookingStep = 'date' | 'guest' | 'otp' | 'confirm'

export interface GuestDetails {
  guest_name: string
  guest_phone: string
  party_size: number
}

export function useBookingWidget() {
  const currentStep = ref<BookingStep>('date')
  const selectedDate = ref<string | null>(null)
  const selectedTime = ref<string | null>(null)
  const guestDetails = ref<GuestDetails | null>(null)
  const guestToken = ref<string | null>(null)
  const widgetConfig = ref<WidgetConfig | null>(null)

  const hasDateAndTime = computed(() => selectedDate.value !== null && selectedTime.value !== null)
  const hasGuestDetails = computed(() => guestDetails.value !== null)
  const hasGuestToken = computed(() => guestToken.value !== null)

  function goTo(step: BookingStep) {
    currentStep.value = step
  }

  function reset() {
    currentStep.value = 'date'
    selectedDate.value = null
    selectedTime.value = null
    guestDetails.value = null
    guestToken.value = null
  }

  return {
    currentStep,
    selectedDate,
    selectedTime,
    guestDetails,
    guestToken,
    widgetConfig,
    hasDateAndTime,
    hasGuestDetails,
    hasGuestToken,
    goTo,
    reset,
  }
}
