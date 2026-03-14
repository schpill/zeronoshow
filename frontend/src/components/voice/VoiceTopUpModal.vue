<script setup lang="ts">
import { computed, ref } from 'vue'

import LoadingSpinner from '@/components/LoadingSpinner.vue'

defineProps<{
  modelValue: boolean
  loading?: boolean
}>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  submit: [amountCents: number]
}>()

const presets = [5, 10, 20, 50]
const selectedPreset = ref<number | null>(10)
const customAmount = ref<number | null>(null)

const finalAmountEuros = computed(() => customAmount.value ?? selectedPreset.value ?? 0)
const finalAmountCents = computed(() => Math.round(finalAmountEuros.value * 100))

function selectPreset(amount: number) {
  selectedPreset.value = amount
  customAmount.value = null
}

function close() {
  emit('update:modelValue', false)
}

function submit() {
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
        <h3 class="text-heading-3">Recharger vos crédits appels</h3>
        <button type="button" class="text-slate-400 hover:text-slate-600" @click="close">
          Fermer
        </button>
      </div>

      <div class="mt-6 grid grid-cols-2 gap-3">
        <button
          v-for="amount in presets"
          :key="amount"
          type="button"
          class="rounded-2xl border py-3 text-sm font-bold"
          :class="
            selectedPreset === amount
              ? 'border-emerald-500 bg-emerald-50 text-emerald-700'
              : 'border-slate-200 text-slate-600'
          "
          @click="selectPreset(amount)"
        >
          {{ amount }} €
        </button>
      </div>

      <div class="mt-6">
        <label for="voice-custom-amount" class="text-label">Autre montant</label>
        <input
          id="voice-custom-amount"
          v-model="customAmount"
          type="number"
          min="1"
          max="100"
          class="mt-2 input-field"
          @input="selectedPreset = null"
        />
      </div>

      <button
        type="button"
        class="mt-8 inline-flex w-full items-center justify-center rounded-2xl bg-emerald-600 py-4 text-sm font-bold text-white disabled:opacity-50"
        :disabled="finalAmountCents <= 0 || loading"
        @click="submit"
      >
        <LoadingSpinner v-if="loading" size="sm" class="mr-2" />
        Recharger avec Stripe
      </button>
    </div>
  </div>
</template>
