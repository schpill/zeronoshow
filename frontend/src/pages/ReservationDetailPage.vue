<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'

import AppLayout from '@/layouts/AppLayout.vue'
import ErrorMessage from '@/components/ErrorMessage.vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'
import ReservationRow from '@/components/ReservationRow.vue'
import SmsLogTable from '@/components/SmsLogTable.vue'
import VoiceCallLogView from '@/components/voice/VoiceCallLogView.vue'
import { useReservations } from '@/composables/useReservations'
import { useToast } from '@/composables/useToast'
import type {
  ReservationCustomer,
  ReservationRecord,
  SmsLogRecord,
  VoiceCallLogRecord,
} from '@/types/reservations'

const route = useRoute()
const { fetchReservation, initiateVoiceCall, loading } = useReservations()
const toast = useToast()

const reservation = ref<ReservationRecord | null>(null)
const customer = ref<ReservationCustomer | null>(null)
const smsLogs = ref<SmsLogRecord[]>([])
const voiceLogs = ref<VoiceCallLogRecord[]>([])
const voiceLoading = ref(false)
const pageError = ref<string | null>(null)

async function loadReservation() {
  pageError.value = null

  try {
    const response = await fetchReservation(route.params.id as string)
    reservation.value = response.reservation
    customer.value = response.customer ?? null
    smsLogs.value = response.sms_logs ?? []
    voiceLogs.value = response.voice_call_logs ?? []
  } catch (error) {
    pageError.value =
      error instanceof Error ? error.message : 'Impossible de charger la reservation.'
  }
}

async function handleVoiceCall() {
  if (!reservation.value) return

  voiceLoading.value = true
  try {
    await initiateVoiceCall(reservation.value.id)
    toast.success('Appel vocal planifié.')
  } catch (error) {
    pageError.value =
      error instanceof Error ? error.message : "Impossible de déclencher l'appel vocal."
  } finally {
    voiceLoading.value = false
  }
}

onMounted(() => {
  void loadReservation()
})

const reservationWithCustomer = computed(() =>
  reservation.value && customer.value
    ? {
        ...reservation.value,
        customer: customer.value,
      }
    : reservation.value,
)

function handleUpdated(updatedReservation: ReservationRecord) {
  reservation.value = updatedReservation
}
</script>

<template>
  <AppLayout>
    <div
      v-if="loading.show.value && !reservation && !pageError"
      class="flex min-h-[240px] items-center justify-center"
    >
      <LoadingSpinner size="lg" label="Chargement de la reservation" />
    </div>

    <ErrorMessage
      v-else-if="pageError"
      title="Impossible de charger la reservation"
      :message="pageError"
      @retry="loadReservation"
    />

    <template v-else>
      <section
        v-if="reservation"
        class="mb-6 rounded-[28px] border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900"
      >
        <p class="text-overline">Réservation</p>
        <h1 class="text-heading-2 mt-2 dark:text-slate-50">{{ reservation.customer_name }}</h1>
        <p class="mt-3 text-body-sm dark:text-slate-400">
          {{ reservation.guests }} couverts · {{ reservation.status }}
        </p>
        <p v-if="customer" class="mt-4 text-body dark:text-slate-300">
          Téléphone: <span class="font-mono">{{ customer.phone }}</span>
        </p>
        <button
          type="button"
          class="mt-4 rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:opacity-60"
          :disabled="voiceLoading"
          @click="handleVoiceCall"
        >
          Lancer un appel vocal
        </button>
      </section>

      <ReservationRow
        v-if="reservationWithCustomer"
        class="mb-6"
        :reservation="reservationWithCustomer"
        @updated="handleUpdated"
      />

      <SmsLogTable :logs="smsLogs" />

      <div class="mt-6">
        <VoiceCallLogView :logs="voiceLogs" :loading="voiceLoading" />
      </div>
    </template>
  </AppLayout>
</template>
