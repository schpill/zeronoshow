<script setup lang="ts">
import type { WidgetStatsResponse } from '@/api/widgetSettings'

defineProps<{
  stats: WidgetStatsResponse | null
  loading: boolean
}>()
</script>

<template>
  <div class="rounded-[32px] border border-slate-200 bg-white p-6">
    <p class="text-overline">Statistiques widget</p>
    <h3 class="mt-2 text-heading-4">Réservations en ligne</h3>

    <div v-if="loading" class="mt-4 text-sm text-slate-500">Chargement...</div>

    <div v-else-if="stats" class="mt-4 grid grid-cols-3 gap-4">
      <div class="text-center">
        <p class="text-2xl font-bold text-slate-900">{{ stats.widget_reservations_count }}</p>
        <p class="mt-1 text-xs text-slate-500">Total</p>
      </div>
      <div class="text-center">
        <p class="text-2xl font-bold text-slate-900">{{ stats.widget_reservations_this_month }}</p>
        <p class="mt-1 text-xs text-slate-500">Ce mois</p>
      </div>
      <div class="text-center">
        <p class="text-2xl font-bold text-slate-900">{{ stats.widget_conversion_rate }}%</p>
        <p class="mt-1 text-xs text-slate-500">Conversion</p>
      </div>
    </div>
  </div>
</template>
