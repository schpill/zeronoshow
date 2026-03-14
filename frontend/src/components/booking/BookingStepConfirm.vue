<script setup lang="ts">
import { ref } from 'vue'
import { createReservation } from '@/api/widget'
import type { GuestDetails } from '@/composables/useBookingWidget'

const props = defineProps<{
  businessToken: string
  selectedDate: string
  selectedTime: string
  guestDetails: GuestDetails
  guestToken: string
  accentColour: string
}>()

const emit = defineEmits<{
  confirmed: []
  conflict: []
}>()

const confirming = ref(false)
const error = ref<string | null>(null)

async function confirm() {
  confirming.value = true
  error.value = null

  try {
    await createReservation(props.businessToken, {
      guest_token: props.guestToken,
      party_size: props.guestDetails.party_size,
      date: props.selectedDate,
      time: props.selectedTime,
      guest_name: props.guestDetails.guest_name,
      guest_phone: props.guestDetails.guest_phone,
    })
    emit('confirmed')
  } catch (err: unknown) {
    const e = err as { status?: number; data?: { error?: { message?: string } } }
    if (e.status === 409) {
      emit('conflict')
    } else {
      error.value = e.data?.error?.message ?? 'Erreur lors de la réservation.'
    }
  } finally {
    confirming.value = false
  }
}
</script>

<template>
  <div>
    <h3 class="text-heading-4 mb-4">Confirmer la réservation</h3>

    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 mb-6 space-y-2">
      <div class="flex justify-between text-sm">
        <span class="text-slate-500">Date</span>
        <span class="font-medium text-slate-800">{{ selectedDate }}</span>
      </div>
      <div class="flex justify-between text-sm">
        <span class="text-slate-500">Heure</span>
        <span class="font-medium text-slate-800">{{ selectedTime }}</span>
      </div>
      <div class="flex justify-between text-sm">
        <span class="text-slate-500">Nom</span>
        <span class="font-medium text-slate-800">{{ guestDetails.guest_name }}</span>
      </div>
      <div class="flex justify-between text-sm">
        <span class="text-slate-500">Couverts</span>
        <span class="font-medium text-slate-800">{{ guestDetails.party_size }}</span>
      </div>
    </div>

    <p v-if="error" class="mb-4 text-sm text-red-600">{{ error }}</p>

    <button
      type="button"
      :disabled="confirming"
      class="w-full rounded-2xl px-5 py-3 text-sm font-semibold text-white transition-opacity disabled:opacity-40"
      :style="{ background: accentColour }"
      @click="confirm"
    >
      <span v-if="confirming">Confirmation en cours...</span>
      <span v-else>Confirmer ma réservation</span>
    </button>
  </div>
</template>
