<script setup lang="ts">
import type { ReviewStats } from '@/api/crm'

defineProps<{
  stats: ReviewStats
}>()
</script>

<template>
  <section class="grid gap-4 md:grid-cols-3">
    <article class="rounded-[28px] border border-slate-200 bg-white p-5">
      <p class="text-overline">Demandes envoyées</p>
      <p class="mt-3 text-heading-2">{{ stats.total_sent }}</p>
    </article>
    <article class="rounded-[28px] border border-slate-200 bg-white p-5">
      <p class="text-overline">Avis cliqués</p>
      <p class="mt-3 text-heading-2">{{ stats.total_clicked }}</p>
    </article>
    <article class="rounded-[28px] border border-slate-200 bg-white p-5">
      <p class="text-overline">Taux de clic</p>
      <p class="mt-3 text-heading-2">{{ Math.round(stats.click_rate_percent) }}%</p>
    </article>
  </section>
</template>
