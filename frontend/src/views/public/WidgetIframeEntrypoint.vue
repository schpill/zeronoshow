<script setup lang="ts">
import { onMounted, onUnmounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { getWidgetConfig } from '@/api/widget'
import { useBookingWidget } from '@/composables/useBookingWidget'
import BookingStepDate from '@/components/booking/BookingStepDate.vue'
import BookingStepGuest from '@/components/booking/BookingStepGuest.vue'
import BookingStepOtp from '@/components/booking/BookingStepOtp.vue'
import BookingStepConfirm from '@/components/booking/BookingStepConfirm.vue'
import type { WidgetConfig } from '@/api/widget'

const route = useRoute()
const businessToken = route.params.businessToken as string

const widget = useBookingWidget()
const loading = ref(true)
const disabled = ref(false)

function sendResize() {
  const height = document.documentElement.scrollHeight
  window.parent.postMessage({ type: 'zns:resize', height }, '*')
}

function sendBooked() {
  window.parent.postMessage({ type: 'zns:booked' }, '*')
}

function onMessage(event: MessageEvent) {
  if (event.data?.type === 'zns:reset') {
    widget.reset()
    widget.currentStep.value = 'date'
  }
}

onMounted(async () => {
  window.addEventListener('message', onMessage)

  try {
    const response = await getWidgetConfig(businessToken)
    widget.widgetConfig.value = response.config
    if (!response.config.is_enabled) {
      disabled.value = true
    }
  } catch {
    disabled.value = true
  } finally {
    loading.value = false
    sendResize()
  }
})

onUnmounted(() => {
  window.removeEventListener('message', onMessage)
})

watch(widget.currentStep, () => {
  setTimeout(sendResize, 100)
})

function onDateSelected(date: string, time: string) {
  widget.selectedDate.value = date
  widget.selectedTime.value = time
  widget.goTo('guest')
}

function onGuestSubmit(details: { guest_name: string; guest_phone: string; party_size: number }) {
  widget.guestDetails.value = details
  widget.goTo('otp')
}

function onOtpVerified(guestToken: string) {
  widget.guestToken.value = guestToken
  widget.goTo('confirm')
}

async function onConfirmed() {
  sendBooked()
  widget.reset()
}
</script>

<template>
  <div class="min-h-screen bg-white px-4 py-6">
    <div class="mx-auto max-w-lg">
      <div v-if="loading" class="py-12 text-center text-sm text-slate-500">Chargement...</div>
      <div v-else-if="disabled" class="py-12 text-center text-sm text-slate-500">
        Widget désactivé.
      </div>
      <template v-else>
        <BookingStepDate
          v-if="widget.currentStep.value === 'date'"
          :business-token="businessToken"
          :accent-colour="widget.widgetConfig.value?.accent_colour ?? '#6366f1'"
          :max-advance-days="widget.widgetConfig.value?.advance_booking_days ?? 60"
          @select="onDateSelected"
        />
        <BookingStepGuest
          v-else-if="widget.currentStep.value === 'guest'"
          :max-party-size="widget.widgetConfig.value?.max_party_size ?? 20"
          :accent-colour="widget.widgetConfig.value?.accent_colour ?? '#6366f1'"
          @submit="onGuestSubmit"
        />
        <BookingStepOtp
          v-else-if="widget.currentStep.value === 'otp'"
          :business-token="businessToken"
          :phone="widget.guestDetails.value?.guest_phone ?? ''"
          :accent-colour="widget.widgetConfig.value?.accent_colour ?? '#6366f1'"
          @verified="onOtpVerified"
        />
        <BookingStepConfirm
          v-else-if="widget.currentStep.value === 'confirm'"
          :business-token="businessToken"
          :selected-date="widget.selectedDate.value ?? ''"
          :selected-time="widget.selectedTime.value ?? ''"
          :guest-details="widget.guestDetails.value!"
          :guest-token="widget.guestToken.value ?? ''"
          :accent-colour="widget.widgetConfig.value?.accent_colour ?? '#6366f1'"
          @confirmed="onConfirmed"
          @conflict="widget.reset()"
        />
      </template>
    </div>
  </div>
</template>
