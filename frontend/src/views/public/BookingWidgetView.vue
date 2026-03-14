<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { getWidgetConfig } from '@/api/widget'
import { useBookingWidget } from '@/composables/useBookingWidget'
import BookingStepDate from '@/components/booking/BookingStepDate.vue'
import BookingStepGuest from '@/components/booking/BookingStepGuest.vue'
import BookingStepOtp from '@/components/booking/BookingStepOtp.vue'
import BookingStepConfirm from '@/components/booking/BookingStepConfirm.vue'

const route = useRoute()
const router = useRouter()
const businessToken = route.params.businessToken as string

const widget = useBookingWidget()
const loading = ref(true)
const disabled = ref(false)
const disabledMessage = ref<string | null>(null)

onMounted(async () => {
  try {
    const response = await getWidgetConfig(businessToken)
    widget.widgetConfig.value = response.config
    if (!response.config.is_enabled) {
      disabled.value = true
      disabledMessage.value = 'Le widget de réservation est actuellement désactivé.'
    }
  } catch (err: unknown) {
    const e = err as { status?: number; data?: { error?: { message?: string } } }
    if (e.status === 423) {
      disabled.value = true
      disabledMessage.value = e.data?.error?.message ?? 'Widget désactivé.'
    } else {
      disabledMessage.value = 'Impossible de charger le widget de réservation.'
    }
  } finally {
    loading.value = false
  }
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

function onConfirmed() {
  router.push({
    name: 'booking-success',
    params: { businessToken },
    query: {
      date: widget.selectedDate.value ?? undefined,
      time: widget.selectedTime.value ?? undefined,
      name: widget.guestDetails.value?.guest_name,
      party: String(widget.guestDetails.value?.party_size),
    },
  })
}

function onConflict() {
  widget.reset()
}
</script>

<template>
  <div
    class="min-h-screen bg-slate-50 px-4 py-8"
    :style="{ '--widget-accent': widget.widgetConfig.value?.accent_colour ?? '#6366f1' }"
  >
    <div class="mx-auto max-w-lg">
      <div v-if="widget.widgetConfig.value?.logo_url" class="mb-6 text-center">
        <img
          :src="widget.widgetConfig.value.logo_url"
          alt="Logo"
          class="mx-auto h-12 object-contain"
        />
      </div>

      <div class="rounded-[32px] border border-slate-200 bg-white p-6 shadow-sm">
        <div v-if="loading" class="py-12 text-center text-sm text-slate-500">Chargement...</div>

        <div v-else-if="disabled" class="py-12 text-center">
          <p class="text-body-sm text-slate-500">{{ disabledMessage }}</p>
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
            @conflict="onConflict"
          />
        </template>
      </div>

      <p class="mt-4 text-center text-xs text-slate-400">Propulsé par ZeroNoShow</p>
    </div>
  </div>
</template>
