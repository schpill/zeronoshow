<script setup lang="ts">
import type { WidgetSettingsRecord } from '@/api/widgetSettings'

defineProps<{
  settings: WidgetSettingsRecord
  loading: boolean
}>()

const emit = defineEmits<{
  edit: []
}>()
</script>

<template>
  <div class="rounded-[32px] border border-slate-200 bg-white p-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
      <div>
        <p class="text-overline">Widget de réservation</p>
        <h3 class="mt-2 text-heading-4">Paramètres</h3>
      </div>
      <button
        type="button"
        class="rounded-2xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
        @click="emit('edit')"
      >
        Modifier
      </button>
    </div>

    <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3">
      <div class="text-sm">
        <span class="text-slate-500">Statut</span>
        <p class="font-medium" :class="settings.is_enabled ? 'text-emerald-700' : 'text-red-700'">
          {{ settings.is_enabled ? 'Activé' : 'Désactivé' }}
        </p>
      </div>
      <div class="text-sm">
        <span class="text-slate-500">Couleur</span>
        <p class="flex items-center gap-1 font-medium text-slate-800">
          <span
            class="inline-block h-4 w-4 rounded-full border border-slate-200"
            :style="{ background: settings.accent_colour }"
          />
          {{ settings.accent_colour }}
        </p>
      </div>
      <div class="text-sm">
        <span class="text-slate-500">Max couverts</span>
        <p class="font-medium text-slate-800">{{ settings.max_party_size }}</p>
      </div>
      <div class="text-sm">
        <span class="text-slate-500">Avance (jours)</span>
        <p class="font-medium text-slate-800">{{ settings.advance_booking_days }}</p>
      </div>
      <div class="text-sm">
        <span class="text-slate-500">Coupure jour J</span>
        <p class="font-medium text-slate-800">{{ settings.same_day_cutoff_minutes }} min</p>
      </div>
    </div>
  </div>
</template>
