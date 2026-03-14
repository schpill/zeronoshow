<script setup lang="ts">
import { reactive, watch } from 'vue'

import { useVoiceSettings } from '@/composables/useVoiceSettings'

const voiceSettings = useVoiceSettings()
const form = reactive({
  auto_call_enabled: false,
  score_threshold: null as number | null,
  min_party_size: null as number | null,
  retry_count: 2,
  retry_delay_minutes: 10,
})
const validationError = defineModel<string | null>('validationError', { default: null })

watch(
  () => voiceSettings.settings.value,
  (settings) => {
    if (!settings) return
    form.auto_call_enabled = settings.auto_call_enabled
    form.score_threshold = settings.auto_call_score_threshold
    form.min_party_size = settings.auto_call_min_party_size
    form.retry_count = settings.retry_count
    form.retry_delay_minutes = settings.retry_delay_minutes
  },
  { immediate: true },
)

async function save() {
  validationError.value = null

  if (form.auto_call_enabled && form.score_threshold === null && form.min_party_size === null) {
    validationError.value = 'Définissez au moins un critère pour activer les appels automatiques.'
    return
  }

  await voiceSettings.updateSettings({
    auto_call_enabled: form.auto_call_enabled,
    score_threshold: form.score_threshold,
    min_party_size: form.min_party_size,
    retry_count: form.retry_count,
    retry_delay_minutes: form.retry_delay_minutes,
  })
}
</script>

<template>
  <section class="rounded-[32px] border border-slate-200 bg-white p-6">
    <div class="flex items-start justify-between gap-4">
      <div>
        <p class="text-overline">Automatisation</p>
        <h2 class="mt-2 text-heading-3">Réglages des appels</h2>
        <p class="mt-2 text-sm text-slate-600">
          Léo appellera automatiquement les clients à risque avant leur réservation.
        </p>
      </div>
      <input
        data-test="auto-call-toggle"
        v-model="form.auto_call_enabled"
        type="checkbox"
        class="h-5 w-5"
      />
    </div>

    <div v-if="form.auto_call_enabled" class="mt-6 grid gap-4 md:grid-cols-2">
      <div>
        <label class="text-label" for="voice-score-threshold">Seuil de score</label>
        <input
          id="voice-score-threshold"
          data-test="score-threshold"
          v-model="form.score_threshold"
          type="number"
          min="0"
          max="100"
          class="mt-2 input-field"
        />
      </div>
      <div>
        <label class="text-label" for="voice-min-party">Taille minimale du groupe</label>
        <input
          id="voice-min-party"
          data-test="min-party-size"
          v-model="form.min_party_size"
          type="number"
          min="2"
          max="50"
          class="mt-2 input-field"
        />
      </div>
    </div>

    <div class="mt-6 grid gap-4 md:grid-cols-2">
      <div>
        <label class="text-label" for="voice-retry-count">Tentatives</label>
        <select id="voice-retry-count" v-model="form.retry_count" class="mt-2 input-field">
          <option :value="0">0</option>
          <option :value="1">1</option>
          <option :value="2">2</option>
          <option :value="3">3</option>
        </select>
      </div>
      <div>
        <label class="text-label" for="voice-retry-delay">Délai entre tentatives</label>
        <select
          id="voice-retry-delay"
          v-model="form.retry_delay_minutes"
          class="mt-2 input-field"
        >
          <option :value="5">5 min</option>
          <option :value="10">10 min</option>
          <option :value="15">15 min</option>
          <option :value="30">30 min</option>
        </select>
      </div>
    </div>

    <p v-if="validationError || voiceSettings.error.value" class="mt-4 text-sm font-semibold text-red-600">
      {{ validationError ?? voiceSettings.error.value }}
    </p>

    <button
      data-test="save-settings"
      type="button"
      class="mt-6 rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white disabled:opacity-50"
      :disabled="voiceSettings.loading.value"
      @click="save"
    >
      Enregistrer les réglages
    </button>
  </section>
</template>
