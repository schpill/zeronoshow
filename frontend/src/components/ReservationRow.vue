<script setup lang="ts">
import { computed, ref } from 'vue'

import ReliabilityBadge from '@/components/ReliabilityBadge.vue'
import StatusBadge from '@/components/StatusBadge.vue'
import { useReservations } from '@/composables/useReservations'
import type { ReservationRecord } from '@/types/reservations'

const props = defineProps<{
  reservation: ReservationRecord
}>()

const emit = defineEmits<{
  updated: [reservation: ReservationRecord]
}>()

const { updateStatus, loading } = useReservations()
const localError = ref<string | null>(null)

const displayTime = computed(() => {
  const date = new Date(props.reservation.scheduled_at)
  const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone

  return new Intl.DateTimeFormat('fr-FR', {
    hour: '2-digit',
    minute: '2-digit',
    timeZone: timezone,
  }).format(date)
})

const showUndo = computed(() => {
  if (props.reservation.status !== 'no_show' || !props.reservation.status_changed_at) {
    return false
  }

  return Date.now() - new Date(props.reservation.status_changed_at).getTime() <= 30 * 60 * 1000
})

async function submit(status: 'show' | 'no_show') {
  localError.value = null

  try {
    const response = await updateStatus(props.reservation.id, status)
    emit('updated', response.reservation)
  } catch (error) {
    localError.value = error instanceof Error ? error.message : 'Mise à jour impossible.'
  }
}
</script>

<template>
  <article
    data-test="reservation-row"
    class="grid gap-4 rounded-[24px] border border-slate-200 bg-white px-4 py-4 shadow-sm dark:border-slate-800 dark:bg-slate-900"
  >
    <div class="flex flex-wrap items-start justify-between gap-3">
      <div>
        <p class="text-heading-4 dark:text-slate-50">{{ reservation.customer_name }}</p>
        <p class="text-body-sm mt-1 dark:text-slate-400">
          {{ displayTime }} · {{ reservation.guests }} couverts
        </p>
      </div>

      <div class="flex flex-wrap items-center gap-2">
        <span
          v-if="reservation.source === 'widget'"
          class="rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-semibold text-indigo-800"
          data-test="source-badge"
        >
          Widget
        </span>
        <ReliabilityBadge
          v-if="reservation.customer"
          :score="reservation.customer.reliability_score"
          :tier="reservation.customer.score_tier"
        />
        <StatusBadge :status="reservation.status" />
      </div>
    </div>

    <div class="flex flex-wrap items-center gap-2">
      <button
        data-test="mark-show"
        type="button"
        class="rounded-2xl bg-emerald-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-600 disabled:opacity-60"
        :disabled="loading.updateStatus.value"
        @click="submit('show')"
      >
        Présent
      </button>
      <button
        data-test="mark-no-show"
        type="button"
        class="rounded-2xl border border-red-200 bg-red-50 px-4 py-2 text-sm font-semibold text-red-800 transition hover:bg-red-100 disabled:opacity-60 dark:border-red-900 dark:bg-red-950/30 dark:text-red-200"
        :disabled="loading.updateStatus.value"
        @click="submit('no_show')"
      >
        No-show
      </button>
      <button
        v-if="showUndo"
        data-test="undo-no-show"
        type="button"
        class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2 text-sm font-medium text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
        :disabled="loading.updateStatus.value"
        @click="submit('show')"
      >
        Annuler le no-show
      </button>
    </div>

    <p
      v-if="localError"
      class="rounded-2xl bg-red-100 px-3 py-2 text-sm text-red-800 dark:bg-red-950/30 dark:text-red-200"
    >
      {{ localError }}
    </p>
  </article>
</template>
