<script setup lang="ts">
import { ref } from 'vue'

import LoadingSpinner from '@/components/LoadingSpinner.vue'

const props = defineProps<{
  initialCapCents: number
  initialAutoRenew: boolean
  loading?: boolean
}>()

const emit = defineEmits<{
  save: [capCents: number, autoRenew: boolean]
  cancel: []
}>()

const capEuros = ref(props.initialCapCents / 100)
const autoRenew = ref(props.initialAutoRenew)
const error = ref<string | null>(null)

function submit() {
  error.value = null
  const cents = Math.round(capEuros.value * 100)

  if (cents < 100 && cents !== 0) {
    error.value = 'Le budget minimum est de 1 €.'
    return
  }

  if (cents > 10000) {
    error.value = 'Le budget maximum est de 100 €.'
    return
  }

  emit('save', cents, autoRenew.value)
}
</script>

<template>
  <section class="rounded-[32px] border border-slate-200 bg-slate-50 p-6">
    <div class="flex items-center justify-between">
      <h3 class="text-heading-3">Modifier le budget appels</h3>
      <button type="button" class="text-sm font-semibold text-slate-500" @click="$emit('cancel')">
        Fermer
      </button>
    </div>

    <div class="mt-6 grid gap-6 md:grid-cols-2">
      <div>
        <label class="text-label" for="voice-cap">Budget mensuel</label>
        <input
          id="voice-cap"
          v-model="capEuros"
          type="number"
          min="0"
          max="100"
          class="mt-2 input-field"
        />
      </div>
      <label class="flex items-center gap-3 text-sm font-semibold text-slate-700">
        <input v-model="autoRenew" type="checkbox" />
        Renouvellement automatique
      </label>
    </div>

    <p v-if="error" class="mt-4 text-sm font-semibold text-red-600">{{ error }}</p>

    <button
      type="button"
      class="mt-6 inline-flex items-center rounded-2xl bg-slate-900 px-6 py-3 text-sm font-semibold text-white disabled:opacity-50"
      :disabled="loading"
      @click="submit"
    >
      <LoadingSpinner v-if="loading" size="sm" class="mr-2" />
      Enregistrer
    </button>
  </section>
</template>
