<script setup lang="ts">
import type { VoiceCallLogRecord } from '@/types/reservations'

defineProps<{
  logs: VoiceCallLogRecord[]
}>()
</script>

<template>
  <section class="rounded-[28px] border border-slate-200 bg-white p-6">
    <div class="mb-4">
      <p class="text-overline">Logs appels</p>
      <h2 class="text-heading-3">Historique des appels</h2>
    </div>

    <p v-if="logs.length === 0" class="rounded-2xl border border-dashed border-slate-200 px-4 py-8 text-center text-sm text-slate-500">
      Aucun appel vocal envoyé pour cette réservation.
    </p>

    <table v-else class="min-w-full text-left text-sm">
      <thead class="text-slate-500">
        <tr>
          <th class="pb-3">Tentative</th>
          <th class="pb-3">Statut</th>
          <th class="pb-3">Durée</th>
          <th class="pb-3">Coût</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="log in logs" :key="log.id" class="border-t border-slate-100">
          <td class="py-3">{{ log.attempt_number }}</td>
          <td class="py-3">{{ log.status }}</td>
          <td class="py-3">{{ log.duration_seconds ?? '—' }} s</td>
          <td class="py-3">{{ log.cost_cents ? `${(log.cost_cents / 100).toFixed(2)} €` : '—' }}</td>
        </tr>
      </tbody>
    </table>
  </section>
</template>
