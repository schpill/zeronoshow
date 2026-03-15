<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import LoadingSpinner from '@/components/LoadingSpinner.vue'
import { useVoiceCredits } from '@/composables/useVoiceCredits'

const route = useRoute()
const router = useRouter()
const voiceCredits = useVoiceCredits()
const status = ref<'loading' | 'success' | 'cancel' | 'error'>('loading')

async function pollStatus() {
  let attempts = 0

  while (attempts < 10) {
    await voiceCredits.fetchStatus()
    if (voiceCredits.status.value) {
      status.value = 'success'
      return
    }
    attempts++
    await new Promise((resolve) => window.setTimeout(resolve, 2000))
  }

  status.value = 'error'
}

onMounted(async () => {
  const queryStatus = route.query.status as string

  if (queryStatus === 'cancel') {
    status.value = 'cancel'
    return
  }

  if (queryStatus === 'success') {
    await pollStatus()
    return
  }

  status.value = 'error'
})
</script>

<template>

    <div class="flex min-h-[400px] flex-col items-center justify-center text-center">
      <template v-if="status === 'loading'">
        <LoadingSpinner size="lg" label="Confirmation du rechargement..." />
      </template>

      <template v-else-if="status === 'success'">
        <h1 class="text-heading-2">Rechargement réussi</h1>
        <p class="mt-3 text-sm text-slate-600">Votre crédit appels a bien été mis à jour.</p>
        <p v-if="voiceCredits.status.value" class="mt-4 text-lg font-semibold">
          {{ voiceCredits.balanceFormatted.value }}
        </p>
        <button
          type="button"
          class="mt-8 rounded-2xl bg-slate-900 px-8 py-3 font-semibold text-white"
          @click="router.push('/voice')"
        >
          Retour aux appels
        </button>
      </template>

      <template v-else-if="status === 'cancel'">
        <h1 class="text-heading-2">Rechargement annulé</h1>
        <p class="mt-3 text-sm text-slate-600">Aucun crédit n'a été débité.</p>
      </template>

      <template v-else>
        <h1 class="text-heading-2">Une erreur est survenue</h1>
        <p class="mt-3 text-sm text-slate-600">Nous n'avons pas pu confirmer votre rechargement.</p>
      </template>
    </div>

</template>
