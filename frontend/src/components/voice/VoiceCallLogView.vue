<script setup lang="ts">
import type { VoiceCallLogRecord } from '@/types/reservations'

defineProps<{
  logs: VoiceCallLogRecord[]
  loading?: boolean
}>()
</script>

<template>
  <section class="rounded-[28px] border border-slate-200 bg-white p-6">
    <div class="flex items-center justify-between gap-4">
      <div>
        <p class="text-overline">Historique</p>
        <h2 class="mt-2 text-heading-3">Appels vocaux</h2>
      </div>
      <p v-if="loading" class="text-sm text-slate-500">Chargement…</p>
    </div>

    <div
      v-if="logs.length === 0"
      class="mt-4 rounded-2xl border border-dashed border-slate-200 px-4 py-6 text-sm text-slate-500"
    >
      Aucun appel vocal pour cette réservation.
    </div>

    <div v-else class="mt-4 space-y-3">
      <article
        v-for="log in logs"
        :key="log.id"
        class="rounded-2xl border border-slate-100 bg-slate-50 px-4 py-4"
      >
        <div class="flex flex-wrap items-center justify-between gap-3">
          <div>
            <p class="text-sm font-semibold text-slate-900">
              Tentative {{ log.attempt_number }} · {{ log.status_label || log.status }}
            </p>
            <p class="mt-1 text-xs text-slate-500">
              DTMF: {{ log.dtmf_response || '—' }} · Durée: {{ log.duration_seconds ?? 0 }} sec
            </p>
          </div>
          <p class="text-xs font-semibold text-slate-500">
            {{ log.created_at ? new Date(log.created_at).toLocaleString('fr-FR') : '—' }}
          </p>
        </div>
      </article>
    </div>
  </section>
</template>
