<script setup lang="ts">
import { computed } from 'vue'

import type { VoiceCreditStatus } from '@/api/voiceCredits'

const props = defineProps<{
  status: VoiceCreditStatus
}>()

defineEmits<{
  topup: []
  'edit-cap': []
}>()

const balanceFormatted = computed(() =>
  new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(
    props.status.balance_euros,
  ),
)

const capFormatted = computed(() =>
  new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(
    props.status.monthly_cap_euros,
  ),
)

const balancePercent = computed(() => {
  if (props.status.monthly_cap_cents <= 0) return 0
  const percent = (props.status.balance_cents / props.status.monthly_cap_cents) * 100
  return Math.min(100, Math.max(0, percent))
})

const barColorClass = computed(() => {
  if (balancePercent.value > 50) return 'bg-emerald-500'
  if (balancePercent.value > 20) return 'bg-amber-500'
  return 'bg-red-500'
})
</script>

<template>
  <section class="rounded-[32px] border border-slate-200 bg-white p-6">
    <div class="flex items-center justify-between gap-4">
      <div>
        <p class="text-overline">Crédits voix</p>
        <h2 class="mt-2 text-heading-3">Appels automatiques</h2>
      </div>
      <div class="text-right">
        <p class="text-2xl font-bold text-slate-900">{{ balanceFormatted }}</p>
        <p class="text-sm text-slate-500">/ {{ capFormatted }}</p>
      </div>
    </div>

    <p
      v-if="status.low_balance_warning"
      class="mt-4 rounded-2xl border border-red-100 bg-red-50 px-4 py-3 text-sm text-red-800"
    >
      Votre solde est faible. Rechargez pour éviter l'interruption du service.
    </p>

    <div class="mt-6">
      <div
        class="mb-2 flex items-center justify-between text-xs font-semibold uppercase text-slate-500"
      >
        <span>Utilisation du budget mensuel</span>
        <span>{{ Math.round(balancePercent) }}%</span>
      </div>
      <div class="h-3 overflow-hidden rounded-full bg-slate-100">
        <div
          data-test="voice-progress-bar"
          class="h-full transition-all duration-300"
          :class="barColorClass"
          :style="{ width: `${balancePercent}%` }"
        />
      </div>
    </div>

    <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
      <p class="text-sm text-slate-600">
        {{ status.auto_renew ? 'Renouvellement automatique activé' : 'Renouvellement manuel' }}
      </p>
      <div class="flex items-center gap-3">
        <button
          type="button"
          class="text-sm font-semibold text-slate-600 hover:text-slate-900"
          @click="$emit('edit-cap')"
        >
          Modifier le budget
        </button>
        <button
          data-test="voice-topup"
          type="button"
          class="rounded-2xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white"
          @click="$emit('topup')"
        >
          Recharger
        </button>
      </div>
    </div>
  </section>
</template>
