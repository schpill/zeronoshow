<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import LoadingSpinner from '@/components/LoadingSpinner.vue'
import { useWhatsAppCredits } from '@/composables/useWhatsAppCredits'

const route = useRoute()
const router = useRouter()
const waCredits = useWhatsAppCredits()

const status = ref<'loading' | 'success' | 'cancel' | 'error'>('loading')
const previousBalance = ref<number | null>(null)

async function pollStatus() {
  let attempts = 0
  const maxAttempts = 10

  while (attempts < maxAttempts) {
    await waCredits.fetchStatus()
    if (
      waCredits.status.value &&
      (previousBalance.value === null ||
        waCredits.status.value.balance_cents > previousBalance.value)
    ) {
      status.value = 'success'
      return
    }
    attempts++
    await new Promise((resolve) => setTimeout(resolve, 2000))
  }
  status.value = 'success' // Timeout but probably fine
}

onMounted(async () => {
  const queryStatus = route.query.status as string

  if (queryStatus === 'cancel') {
    status.value = 'cancel'
    return
  }

  if (queryStatus === 'success') {
    await pollStatus()
  } else {
    status.value = 'error'
  }
})
</script>

<template>
  <div class="flex min-h-[400px] flex-col items-center justify-center text-center">
    <template v-if="status === 'loading'">
      <LoadingSpinner size="lg" label="Confirmation du rechargement..." />
      <p class="mt-4 text-slate-500">Nous vérifions votre nouveau solde avec Stripe.</p>
    </template>

    <template v-else-if="status === 'success'">
      <div class="flex h-20 w-20 items-center justify-center rounded-full bg-emerald-100 text-4xl">
        ✅
      </div>
      <h1 class="mt-6 text-heading-2">Rechargement réussi !</h1>
      <p class="mt-2 text-body-sm text-slate-600">
        Votre crédit Léo WhatsApp a été mis à jour avec succès.
      </p>
      <div
        v-if="waCredits.status.value"
        class="mt-8 rounded-2xl bg-emerald-50 px-6 py-4 border border-emerald-100"
      >
        <p class="text-sm text-emerald-800">
          Nouveau solde : <span class="font-bold">{{ waCredits.balanceFormatted }}</span>
        </p>
      </div>
      <button
        type="button"
        class="mt-10 rounded-2xl bg-slate-900 px-8 py-3 font-semibold text-white"
        @click="router.push('/leo')"
      >
        Retour à Léo
      </button>
    </template>

    <template v-else-if="status === 'cancel'">
      <div class="flex h-20 w-20 items-center justify-center rounded-full bg-slate-100 text-4xl">
        ⚪
      </div>
      <h1 class="mt-6 text-heading-2">Rechargement annulé</h1>
      <p class="mt-2 text-body-sm text-slate-600">Aucun crédit n'a été débité de votre compte.</p>
      <button
        type="button"
        class="mt-10 rounded-2xl border border-slate-200 px-8 py-3 font-semibold text-slate-700"
        @click="router.push('/leo')"
      >
        Retour à Léo
      </button>
    </template>

    <template v-else>
      <div class="flex h-20 w-20 items-center justify-center rounded-full bg-red-100 text-4xl">
        ❌
      </div>
      <h1 class="mt-6 text-heading-2">Une erreur est survenue</h1>
      <p class="mt-2 text-body-sm text-slate-600">
        Nous n'avons pas pu confirmer votre rechargement.
      </p>
      <button
        type="button"
        class="mt-10 rounded-2xl bg-slate-900 px-8 py-3 font-semibold text-white"
        @click="router.push('/leo')"
      >
        Retour à Léo
      </button>
    </template>
  </div>
</template>
