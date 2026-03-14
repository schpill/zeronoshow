<script setup lang="ts">
import { computed, reactive, watch } from 'vue'

import { useReviewSettings } from '@/composables/useReviewSettings'

const reputation = useReviewSettings()

const form = reactive({
  review_requests_enabled: false,
  review_platform: 'google' as 'google' | 'tripadvisor',
  review_delay_hours: 2,
  google_place_id: '',
  tripadvisor_location_id: '',
})

const validationError = computed(() => {
  if (!form.review_requests_enabled) return null
  if (form.review_platform === 'google' && !form.google_place_id) {
    return 'Le Place ID Google est obligatoire.'
  }
  if (form.review_platform === 'tripadvisor' && !form.tripadvisor_location_id) {
    return 'L’identifiant TripAdvisor est obligatoire.'
  }
  return null
})

watch(
  () => reputation.settings.value,
  (settings) => {
    if (!settings) return
    form.review_requests_enabled = settings.review_requests_enabled
    form.review_platform = settings.review_platform
    form.review_delay_hours = settings.review_delay_hours
    form.google_place_id = settings.google_place_id ?? ''
    form.tripadvisor_location_id = settings.tripadvisor_location_id ?? ''
  },
  { immediate: true },
)

async function save() {
  if (validationError.value) return

  await reputation.updateSettings({
    review_requests_enabled: form.review_requests_enabled,
    review_platform: form.review_platform,
    review_delay_hours: form.review_delay_hours,
    google_place_id: form.google_place_id || null,
    tripadvisor_location_id: form.tripadvisor_location_id || null,
  })
}
</script>

<template>
  <section class="rounded-[32px] border border-slate-200 bg-white p-6">
    <div class="flex items-center justify-between gap-4">
      <div>
        <p class="text-overline">Paramètres avis</p>
        <h2 class="text-heading-3">Configuration</h2>
      </div>
      <label class="flex items-center gap-3 text-sm font-semibold">
        <input
          data-test="review-enabled"
          v-model="form.review_requests_enabled"
          type="checkbox"
          class="h-4 w-4"
        />
        Activer
      </label>
    </div>

    <div class="mt-6 grid gap-5">
      <div class="grid gap-3 md:grid-cols-2">
        <label class="rounded-2xl border border-slate-200 px-4 py-3">
          <input
            id="review-platform-google"
            v-model="form.review_platform"
            type="radio"
            value="google"
            name="review-platform"
            class="mr-2"
          />
          Google
        </label>
        <label class="rounded-2xl border border-slate-200 px-4 py-3">
          <input
            id="review-platform-tripadvisor"
            v-model="form.review_platform"
            type="radio"
            value="tripadvisor"
            name="review-platform"
            class="mr-2"
          />
          TripAdvisor
        </label>
      </div>

      <div>
        <label for="review-delay" class="text-label">Délai</label>
        <select id="review-delay" v-model="form.review_delay_hours" class="mt-2 input-field">
          <option v-for="delay in [0, 1, 2, 4, 8, 24, 48]" :key="delay" :value="delay">
            {{ delay }}h
          </option>
        </select>
      </div>

      <div v-if="form.review_platform === 'google'">
        <label for="google-place-id" class="text-label">Google Place ID</label>
        <input
          id="google-place-id"
          v-model="form.google_place_id"
          type="text"
          class="mt-2 input-field"
        />
      </div>

      <div v-else>
        <label for="tripadvisor-location-id" class="text-label">TripAdvisor Location ID</label>
        <input
          id="tripadvisor-location-id"
          v-model="form.tripadvisor_location_id"
          type="text"
          class="mt-2 input-field"
        />
      </div>

      <p v-if="validationError" class="text-sm text-red-700">{{ validationError }}</p>
      <p v-if="reputation.error.value" class="text-sm text-red-700">{{ reputation.error.value }}</p>

      <button
        data-test="save-review-settings"
        type="button"
        class="rounded-2xl bg-emerald-600 px-4 py-3 text-sm font-semibold text-white"
        :disabled="reputation.loading.value"
        @click="save"
      >
        Enregistrer
      </button>
    </div>
  </section>
</template>
