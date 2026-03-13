<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'

import AppLayout from '@/layouts/AppLayout.vue'
import ErrorMessage from '@/components/ErrorMessage.vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'
import ReservationRow from '@/components/ReservationRow.vue'
import SmsLogTable from '@/components/SmsLogTable.vue'
import { useReservations } from '@/composables/useReservations'
import type { ReservationCustomer, ReservationRecord, SmsLogRecord } from '@/types/reservations'

const route = useRoute()
const { fetchReservation, loading } = useReservations()

const reservation = ref<ReservationRecord | null>(null)
const customer = ref<ReservationCustomer | null>(null)
const smsLogs = ref<SmsLogRecord[]>([])
const pageError = ref<string | null>(null)

async function loadReservation() {
  pageError.value = null

  try {
    const response = await fetchReservation(route.params.id as string)
    reservation.value = response.reservation
    customer.value = response.customer ?? null
    smsLogs.value = response.sms_logs ?? []
  } catch (error) {
    pageError.value = error instanceof Error ? error.message : 'Impossible de charger la reservation.'
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
    </section>

    <ReservationRow
      v-if="reservationWithCustomer"
      class="mb-6"
      :reservation="reservationWithCustomer"
      @updated="handleUpdated"
    />

    <SmsLogTable :logs="smsLogs" />
    </template>
  </AppLayout>
</template>
