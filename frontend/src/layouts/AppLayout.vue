<script setup lang="ts">
import { computed } from 'vue'

import NavBar from '@/components/NavBar.vue'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()

const trialBanner = computed(() => {
  if (!auth.user?.trial_ends_at || auth.user.subscription_status !== 'trial') {
    return null
  }

  const diff = new Date(auth.user.trial_ends_at).getTime() - Date.now()
  const daysRemaining = Math.ceil(diff / (1000 * 60 * 60 * 24))

  if (daysRemaining < 0) {
    return {
      tone: 'danger',
      message: 'Essai gratuit expiré. Abonnez-vous pour continuer à créer des réservations.',
    }
  }

  if (daysRemaining < 3) {
    return {
      tone: 'warning',
      message: `Essai gratuit: ${daysRemaining} jour${daysRemaining > 1 ? 's' : ''} restant${daysRemaining > 1 ? 's' : ''}.`,
    }
  }

  return null
})
</script>

<template>
  <div class="min-h-screen bg-slate-50 text-slate-900 dark:bg-slate-950 dark:text-slate-50">
    <NavBar />

    <div
      v-if="trialBanner"
      class="border-b px-4 py-3 text-center text-sm font-medium"
      :class="
        trialBanner.tone === 'danger'
          ? 'border-red-200 bg-red-50 text-red-800 dark:border-red-900/60 dark:bg-red-900/20 dark:text-red-300'
          : 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900/60 dark:bg-amber-900/20 dark:text-amber-300'
      "
    >
      {{ trialBanner.message }}
    </div>

    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
      <slot />
    </main>
  </div>
</template>
