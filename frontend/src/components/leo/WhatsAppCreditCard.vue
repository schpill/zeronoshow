<script setup lang="ts">
import { computed } from 'vue'

import type { WhatsAppCreditStatus } from '@/api/whatsappCredits'

const props = defineProps<{
  status: WhatsAppCreditStatus
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

const textColorClass = computed(() => {
  if (balancePercent.value > 50) return 'text-emerald-600'
  if (balancePercent.value > 20) return 'text-amber-600'
  return 'text-red-600'
})
</script>

<template>
  <div class="mt-6 rounded-[32px] border border-slate-200 bg-white p-6">
    <div class="flex items-center justify-between">
      <h3 class="text-heading-3">Crédit Léo WhatsApp</h3>
      <div class="flex items-center gap-2">
        <span class="text-2xl font-bold" :class="textColorClass">{{ balanceFormatted }}</span>
        <span class="text-sm text-slate-500">/ {{ capFormatted }}</span>
      </div>
    </div>

    <div
      v-if="status.low_balance_warning"
      class="mt-4 flex items-center gap-3 rounded-2xl bg-red-50 px-4 py-3 text-sm text-red-800 border border-red-100"
    >
      <span class="text-lg">⚠️</span>
      <p>Votre solde est faible. Rechargez pour éviter l'interruption du service.</p>
    </div>

    <div class="mt-6">
      <div
        class="flex justify-between text-xs font-semibold text-slate-500 uppercase tracking-wider"
      >
        <span>Utilisation du budget mensuel</span>
        <span>{{ Math.round(balancePercent) }}%</span>
      </div>
      <div class="mt-2 h-3 w-full overflow-hidden rounded-full bg-slate-100">
        <div
          class="h-full transition-all duration-500"
          :class="barColorClass"
          :style="{ width: `${balancePercent}%` }"
          role="progressbar"
          :aria-valuenow="status.balance_cents"
          :aria-valuemin="0"
          :aria-valuemax="status.monthly_cap_cents"
        ></div>
      </div>
    </div>

    <div class="mt-6 flex flex-wrap items-center justify-between gap-4">
      <div class="text-sm text-slate-600">
        <span v-if="status.auto_renew" class="flex items-center gap-1.5">
          <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
          Renouvellement automatique le 1er du mois
        </span>
        <span v-else class="flex items-center gap-1.5 text-slate-400">
          <span class="h-1.5 w-1.5 rounded-full bg-slate-300"></span>
          Renouvellement manuel
        </span>
      </div>
      <div class="flex items-center gap-3">
        <button
          type="button"
          class="text-sm font-semibold text-slate-600 hover:text-slate-900"
          @click="$emit('edit-cap')"
        >
          Modifier le budget
        </button>
        <button
          type="button"
          class="rounded-2xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800"
          @click="$emit('topup')"
        >
          Recharger
        </button>
      </div>
    </div>
  </div>
</template>
