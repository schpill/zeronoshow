<script setup lang="ts">
import { computed, ref, watch } from 'vue'

import ReservationRow from '@/components/ReservationRow.vue'
import type { ReservationRecord } from '@/types/reservations'

const props = defineProps<{
  reservations: ReservationRecord[]
  loading: boolean
}>()

const emit = defineEmits<{
  updated: [reservation: ReservationRecord]
}>()

const localReservations = ref<ReservationRecord[]>([])

watch(
  () => props.reservations,
  (reservations) => {
    localReservations.value = [...reservations]
  },
  { immediate: true },
)

const sortedReservations = computed(() =>
  [...localReservations.value].sort(
    (left, right) => new Date(left.scheduled_at).getTime() - new Date(right.scheduled_at).getTime(),
  ),
)

function handleUpdated(updatedReservation: ReservationRecord) {
  localReservations.value = localReservations.value.map((reservation) =>
    reservation.id === updatedReservation.id ? updatedReservation : reservation,
  )
  emit('updated', updatedReservation)
}
</script>

<template>
  <section
    class="grid gap-4 rounded-[32px] border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900"
  >
    <div class="flex items-center justify-between gap-3">
      <div>
        <p class="text-overline">Service du jour</p>
        <h2 class="text-heading-3 dark:text-slate-50">Réservations à suivre</h2>
      </div>
    </div>

    <div v-if="loading" class="grid gap-3">
      <div
        v-for="index in 3"
        :key="index"
        data-test="reservation-skeleton"
        class="h-28 animate-pulse rounded-[24px] border border-slate-200 bg-slate-100 dark:border-slate-800 dark:bg-slate-800"
      />
    </div>

    <p
      v-else-if="sortedReservations.length === 0"
      class="rounded-[24px] border border-dashed border-slate-200 px-4 py-10 text-center text-body-sm dark:border-slate-700 dark:text-slate-400"
    >
      Aucune réservation pour cette journée.
    </p>

    <div v-else class="grid gap-3">
      <ReservationRow
        v-for="reservation in sortedReservations"
        :key="reservation.id"
        :reservation="reservation"
        @updated="handleUpdated"
      />
    </div>
  </section>
</template>
