<script setup lang="ts">
import { ref, watch } from 'vue'
import type { WidgetSettingsRecord, UpdateWidgetSettingsPayload } from '@/api/widgetSettings'

const props = defineProps<{
  settings: WidgetSettingsRecord
  loading: boolean
}>()

const emit = defineEmits<{
  save: [payload: UpdateWidgetSettingsPayload]
  cancel: []
}>()

const form = ref<UpdateWidgetSettingsPayload>({
  logo_url: props.settings.logo_url,
  accent_colour: props.settings.accent_colour,
  max_party_size: props.settings.max_party_size,
  advance_booking_days: props.settings.advance_booking_days,
  same_day_cutoff_minutes: props.settings.same_day_cutoff_minutes,
  is_enabled: props.settings.is_enabled,
})

watch(
  () => props.settings,
  (s) => {
    form.value = {
      logo_url: s.logo_url,
      accent_colour: s.accent_colour,
      max_party_size: s.max_party_size,
      advance_booking_days: s.advance_booking_days,
      same_day_cutoff_minutes: s.same_day_cutoff_minutes,
      is_enabled: s.is_enabled,
    }
  },
  { deep: true },
)

function save() {
  emit('save', { ...form.value })
}
</script>

<template>
  <div>
    <h3 class="text-heading-4 mb-4">Paramètres du widget</h3>

    <form class="space-y-4" @submit.prevent="save">
      <div>
        <label class="text-label mb-1 block" for="setting-logo">URL du logo</label>
        <input
          id="setting-logo"
          v-model="form.logo_url"
          type="url"
          class="input-field"
          placeholder="https://..."
        />
      </div>

      <div>
        <label class="text-label mb-1 block" for="setting-colour">Couleur d'accentuation</label>
        <div class="flex items-center gap-3">
          <input
            id="setting-colour"
            v-model="form.accent_colour"
            type="color"
            class="h-10 w-14 cursor-pointer rounded-lg border border-slate-200"
          />
          <input
            v-model="form.accent_colour"
            type="text"
            class="input-field flex-1"
            placeholder="#6366f1"
          />
        </div>
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="text-label mb-1 block" for="setting-max">Max couverts</label>
          <input
            id="setting-max"
            v-model.number="form.max_party_size"
            type="number"
            class="input-field"
            min="1"
            max="100"
          />
        </div>
        <div>
          <label class="text-label mb-1 block" for="setting-advance">Jours d'avance</label>
          <input
            id="setting-advance"
            v-model.number="form.advance_booking_days"
            type="number"
            class="input-field"
            min="1"
            max="365"
          />
        </div>
      </div>

      <div>
        <label class="text-label mb-1 block" for="setting-cutoff">Coupure jour J (minutes)</label>
        <input
          id="setting-cutoff"
          v-model.number="form.same_day_cutoff_minutes"
          type="number"
          class="input-field"
          min="0"
          max="1440"
        />
      </div>

      <label class="flex items-center gap-2">
        <input
          v-model="form.is_enabled"
          type="checkbox"
          class="h-4 w-4 rounded border-slate-300 text-emerald-600"
        />
        <span class="text-sm text-slate-700">Widget activé</span>
      </label>

      <div class="flex justify-end gap-3 pt-2">
        <button
          type="button"
          class="rounded-2xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700"
          @click="emit('cancel')"
        >
          Annuler
        </button>
        <button
          type="submit"
          :disabled="loading"
          class="rounded-2xl bg-emerald-600 px-5 py-2 text-sm font-semibold text-white disabled:opacity-40"
        >
          {{ loading ? 'Enregistrement...' : 'Enregistrer' }}
        </button>
      </div>
    </form>
  </div>
</template>
