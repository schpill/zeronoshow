<script setup lang="ts">
import { computed } from 'vue'

import type { ReservationStatus } from '@/types/reservations'

const props = defineProps<{
  status: ReservationStatus
}>()

const display = computed(() => {
  const map: Record<ReservationStatus, { label: string; classes: string }> = {
    pending_verification: {
      label: 'À vérifier',
      classes:
        'bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700',
    },
    pending_reminder: {
      label: 'Confirmé (rappel à venir)',
      classes:
        'bg-blue-100 text-blue-800 border-blue-300 dark:bg-blue-950/40 dark:text-blue-200 dark:border-blue-900',
    },
    confirmed: {
      label: 'Confirmé',
      classes:
        'bg-emerald-100 text-emerald-900 border-emerald-300 dark:bg-emerald-950/40 dark:text-emerald-200 dark:border-emerald-900',
    },
    cancelled_by_client: {
      label: 'Annulé',
      classes:
        'bg-amber-100 text-amber-800 border-amber-300 dark:bg-amber-950/40 dark:text-amber-200 dark:border-amber-900',
    },
    cancelled_no_confirmation: {
      label: 'Annulé (pas de réponse)',
      classes:
        'bg-red-100 text-red-800 border-red-300 dark:bg-red-950/40 dark:text-red-200 dark:border-red-900',
    },
    no_show: {
      label: 'No-show',
      classes:
        'bg-red-100 text-red-800 border-red-300 dark:bg-red-950/40 dark:text-red-200 dark:border-red-900',
    },
    show: {
      label: 'Présent',
      classes:
        'bg-emerald-100 text-emerald-900 border-emerald-300 dark:bg-emerald-950/40 dark:text-emerald-200 dark:border-emerald-900',
    },
  }

  return map[props.status]
})
</script>

<template>
  <span
    class="text-badge inline-flex items-center rounded-full border px-2.5 py-1"
    :class="display.classes"
    :aria-label="`Statut: ${display.label}`"
  >
    {{ display.label }}
  </span>
</template>
