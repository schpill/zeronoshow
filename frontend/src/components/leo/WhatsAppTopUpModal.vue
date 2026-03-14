<script setup lang="ts">
import { ref, computed } from 'vue'

import LoadingSpinner from '@/components/LoadingSpinner.vue'

defineProps<{
  modelValue: boolean
  loading?: boolean
}>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  submit: [amountCents: number]
}>()

const presets = [2, 5, 10, 20, 50]
const selectedPreset = ref<number | null>(5)
const customAmount = ref<number | null>(null)

const finalAmountEuros = computed(() => {
  if (customAmount.value !== null) return customAmount.value
  return selectedPreset.value ?? 0
})

const finalAmountCents = computed(() => Math.round(finalAmountEuros.value * 100))

function selectPreset(amount: number) {
  selectedPreset.value = amount
  customAmount.value = null
}

function handleCustomInput() {
  selectedPreset.value = null
}

function close() {
  emit('update:modelValue', false)
}

function handleSubmit() {
  if (finalAmountCents.value > 0) {
    emit('submit', finalAmountCents.value)
  }
}
</script>

<template>
  <div
    v-if="modelValue"
    class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-950/60 px-4"
  >
    <div class="w-full max-w-lg rounded-[32px] bg-white p-8 shadow-2xl">
      <div class="flex items-center justify-between">
        <h3 class="text-heading-3">Recharger votre crédit</h3>
        <button type="button" class="text-slate-400 hover:text-slate-600" @click="close">
          <span class="sr-only">Fermer</span>
          <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M6 18L18 6M6 6l12 12"
            />
          </svg>
        </button>
      </div>

      <div class="mt-8">
        <label class="text-label">Montants prédéfinis</label>
        <div class="mt-3 grid grid-cols-3 gap-3">
          <button
            v-for="amount in presets"
            :key="amount"
            type="button"
            class="rounded-2xl border py-3 text-sm font-bold transition"
            :class="
              selectedPreset === amount
                ? 'border-emerald-500 bg-emerald-50 text-emerald-700'
                : 'border-slate-200 text-slate-600 hover:border-slate-300'
            "
            @click="selectPreset(amount)"
          >
            {{ amount }} €
          </button>
        </div>
      </div>

      <div class="mt-6">
        <label for="custom-amount" class="text-label">Autre montant (max 100 €)</label>
        <div class="relative mt-3">
          <input
            id="custom-amount"
            v-model="customAmount"
            type="number"
            min="1"
            max="100"
            class="input-field pr-10"
            placeholder="Entrez un montant"
            @input="handleCustomInput"
          />
          <div
            class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-4 text-slate-400"
          >
            €
          </div>
        </div>
      </div>

      <div class="mt-8 rounded-2xl bg-slate-50 p-4 border border-slate-100">
        <div class="flex items-center justify-between font-semibold">
          <span class="text-slate-600">Total à payer</span>
          <span class="text-xl text-slate-900"
            >{{ finalAmountEuros.toFixed(2).replace('.', ',') }} €</span
          >
        </div>
        <p class="mt-2 text-xs text-slate-500 leading-relaxed">
          Le rechargement s'effectue via une page de paiement sécurisée Stripe. Votre crédit sera
          disponible immédiatement après validation du paiement.
        </p>
      </div>

      <div class="mt-8 flex flex-col gap-3">
        <button
          type="button"
          class="flex w-full items-center justify-center rounded-2xl bg-emerald-600 py-4 text-sm font-bold text-white transition hover:bg-emerald-700 disabled:opacity-50"
          :disabled="finalAmountCents <= 0 || loading"
          @click="handleSubmit"
        >
          <LoadingSpinner v-if="loading" size="sm" class="mr-2" />
          Recharger avec Stripe
        </button>
        <button
          type="button"
          class="w-full py-2 text-sm font-semibold text-slate-500 hover:text-slate-700"
          @click="close"
        >
          Annuler
        </button>
      </div>
    </div>
  </div>
</template>
