<script setup lang="ts">
import type { DashboardStats } from '@/types/reservations'

defineProps<{
  stats: DashboardStats
}>()

const cards: Array<{ key: keyof DashboardStats; label: string; tone: string }> = [
  { key: 'confirmed', label: 'Confirmées', tone: 'text-emerald-700 bg-emerald-50 border-emerald-200' },
  { key: 'pending_reminder', label: 'Rappels', tone: 'text-blue-700 bg-blue-50 border-blue-200' },
  { key: 'cancelled', label: 'Annulations', tone: 'text-amber-700 bg-amber-50 border-amber-200' },
  { key: 'no_show', label: 'No-shows', tone: 'text-red-700 bg-red-50 border-red-200' },
]
</script>

<template>
  <section class="grid gap-3 md:grid-cols-4" role="status" aria-label="Indicateurs du dashboard">
    <article
      v-for="card in cards"
      :key="card.key"
      class="rounded-2xl border p-4 dark:border-slate-800 dark:bg-slate-950"
      :class="card.tone"
    >
      <p class="text-caption !text-current/80">{{ card.label }}</p>
      <p class="mt-2 text-3xl font-bold">{{ stats[card.key] }}</p>
    </article>
  </section>
</template>
