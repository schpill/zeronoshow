<script setup lang="ts">
import type { SmsLogRecord } from '@/types/reservations'

defineProps<{
  logs: SmsLogRecord[]
}>()

function formatCost(value: number | null) {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'EUR',
  }).format(value ?? 0)
}

function statusClasses(status: SmsLogRecord['status']) {
  if (status === 'delivered') {
    return 'bg-emerald-100 text-emerald-900'
  }

  if (status === 'failed') {
    return 'bg-red-100 text-red-800'
  }

  return 'bg-slate-100 text-slate-600'
}
</script>

<template>
  <section
    class="rounded-[28px] border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900"
  >
    <div class="mb-4">
      <p class="text-overline">Logs SMS</p>
      <h2 class="text-heading-3 dark:text-slate-50">Historique d’envoi</h2>
    </div>

    <p
      v-if="logs.length === 0"
      class="rounded-2xl border border-dashed border-slate-200 px-4 py-8 text-center text-body-sm dark:border-slate-700 dark:text-slate-400"
    >
      Aucun SMS envoyé pour cette réservation.
    </p>

    <table v-else class="min-w-full text-left text-sm">
      <thead class="text-slate-500 dark:text-slate-400">
        <tr>
          <th class="pb-3">Type</th>
          <th class="pb-3">Statut</th>
          <th class="pb-3">Coût</th>
          <th class="pb-3">Téléphone</th>
          <th class="pb-3">Envoyé</th>
          <th class="pb-3">Livré</th>
        </tr>
      </thead>
      <tbody>
        <tr
          v-for="log in logs"
          :key="log.id"
          class="border-t border-slate-100 dark:border-slate-800"
        >
          <td class="py-3">{{ log.type }}</td>
          <td class="py-3">
            <span class="rounded-full px-2 py-1 text-badge" :class="statusClasses(log.status)">
              {{ log.status }}
            </span>
          </td>
          <td class="py-3">{{ formatCost(log.cost_eur) }}</td>
          <td class="py-3 font-mono text-xs">{{ log.phone }}</td>
          <td class="py-3 text-xs text-slate-500 dark:text-slate-400">{{ log.sent_at ?? '—' }}</td>
          <td class="py-3 text-xs text-slate-500 dark:text-slate-400">
            {{ log.delivered_at ?? '—' }}
          </td>
        </tr>
      </tbody>
    </table>
  </section>
</template>
