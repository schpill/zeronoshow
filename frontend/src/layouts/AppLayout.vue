<script setup lang="ts">
import { computed } from 'vue'

import NavBar from '@/components/NavBar.vue'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()

const daysUntilTrialEnd = computed(() => {
  if (!auth.user?.trial_ends_at || auth.user.subscription_status !== 'trial') {
    return null
  }

  const diff = new Date(auth.user.trial_ends_at).getTime() - Date.now()
  return Math.ceil(diff / (1000 * 60 * 60 * 24))
})
</script>

<template>
  <div class="min-h-screen bg-slate-50 text-slate-900 dark:bg-slate-950 dark:text-slate-50">
    <NavBar />

    <div
      v-if="daysUntilTrialEnd !== null && daysUntilTrialEnd < 3"
      class="border-b border-amber-200 bg-amber-50 px-4 py-3 text-center text-sm font-medium text-amber-800 dark:border-amber-900/60 dark:bg-amber-900/20 dark:text-amber-300"
    >
      Essai gratuit: {{ daysUntilTrialEnd }} jour<span v-if="daysUntilTrialEnd > 1">s</span> restant<span v-if="daysUntilTrialEnd > 1">s</span>.
    </div>

    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
      <slot />
    </main>
  </div>
</template>
