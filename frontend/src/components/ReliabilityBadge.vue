<script setup lang="ts">
import { computed } from 'vue'

import type { ReliabilityTier } from '@/types/reservations'

const props = defineProps<{
  score: number | null
  tier: ReliabilityTier
}>()

const display = computed(() => {
  if (props.tier === 'reliable' && props.score !== null) {
    return {
      label: `Fiable ${props.score}%`,
      classes:
        'bg-emerald-100 text-emerald-900 border-emerald-300 dark:bg-emerald-900/30 dark:text-emerald-300 dark:border-emerald-800',
    }
  }

  if (props.tier === 'average' && props.score !== null) {
    return {
      label: `Moyen ${props.score}%`,
      classes:
        'bg-amber-100 text-amber-800 border-amber-300 dark:bg-amber-900/20 dark:text-amber-300 dark:border-amber-800',
    }
  }

  if (props.tier === 'at_risk' && props.score !== null) {
    return {
      label: `À risque ${props.score}%`,
      classes:
        'bg-red-100 text-red-800 border-red-300 dark:bg-red-900/20 dark:text-red-300 dark:border-red-800',
    }
  }

  return {
    label: 'Inconnu',
    classes:
      'bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700',
  }
})
</script>

<template>
  <span
    class="reliability-badge text-badge inline-flex items-center rounded-full border px-2.5 py-1"
    :class="display.classes"
    :aria-label="`Reliability score: ${display.label}`"
  >
    {{ display.label }}
  </span>
</template>
