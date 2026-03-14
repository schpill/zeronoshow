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

function handleSubmit() {
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
  <div class="mt-6 rounded-[32px] border border-slate-200 bg-slate-50 p-6">
    <div class="flex items-center justify-between">
      <h3 class="text-lg font-bold text-slate-900">Modifier le budget mensuel</h3>
      <button type="button" class="text-sm font-semibold text-slate-500" @click="$emit('cancel')">
        Fermer
      </button>
    </div>

    <div class="mt-6 grid gap-6 md:grid-cols-2">
      <div>
        <label for="monthly-cap" class="text-label">Budget mensuel (max 100 €)</label>
        <div class="relative mt-2">
          <input
            id="monthly-cap"
            v-model="capEuros"
            type="number"
            step="0.01"
            min="0"
            max="100"
            class="input-field pr-10"
          />
          <div
            class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-4 text-slate-400"
          >
            €
          </div>
        </div>
        <p class="mt-2 text-xs text-slate-500">
          Ce montant sera prélevé automatiquement chaque 1er du mois.
        </p>
      </div>

      <div>
        <label class="text-label">Renouvellement automatique</label>
        <div class="mt-2 flex h-[50px] items-center">
          <label class="relative inline-flex cursor-pointer items-center">
            <input v-model="autoRenew" type="checkbox" class="peer sr-only" />
            <div
              class="peer h-6 w-11 rounded-full bg-slate-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-emerald-600 peer-checked:after:translate-x-full peer-checked:after:border-white"
            ></div>
            <span class="ml-3 text-sm font-semibold text-slate-700">
              Activer le rechargement automatique
            </span>
          </label>
        </div>
      </div>
    </div>

    <p v-if="error" class="mt-4 text-sm font-semibold text-red-600">
      {{ error }}
    </p>

    <div class="mt-8 flex items-center justify-end gap-3">
      <button
        type="button"
        class="text-sm font-semibold text-slate-500 hover:text-slate-700"
        @click="$emit('cancel')"
      >
        Annuler
      </button>
      <button
        type="button"
        class="inline-flex items-center rounded-2xl bg-slate-900 px-6 py-3 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:opacity-50"
        :disabled="loading"
        @click="handleSubmit"
      >
        <LoadingSpinner v-if="loading" size="sm" class="mr-2" />
        Enregistrer les modifications
      </button>
    </div>
  </div>
</template>
