<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { RouterView } from 'vue-router'

import NavBar from '@/components/NavBar.vue'
import OnboardingTour from '@/components/help/OnboardingTour.vue'
import ToastContainer from '@/components/ToastContainer.vue'
import TrialBanner from '@/components/TrialBanner.vue'
import { TOUR_STEPS } from '@/composables/useOnboardingTour'
import { useAuthStore } from '@/stores/auth'
import { apiClient } from '@/api/axios'

const auth = useAuthStore()

const showTour = ref(false)

onMounted(() => {
  auth.captureImpersonationTokenFromUrl()
})

watch(
  () => auth.user?.onboarding_completed_at,
  (val) => {
    showTour.value = val === null && auth.user !== null
  },
  { immediate: true },
)

async function handleTourComplete() {
  showTour.value = false
  try {
    const response = await apiClient.patch<{ onboarding_completed_at: string }>(
      '/business/onboarding-complete',
    )
    if (auth.user) {
      auth.user = { ...auth.user, onboarding_completed_at: response.onboarding_completed_at }
    }
  } catch {
    // Best effort
  }
}

async function handleTourSkip() {
  await handleTourComplete()
}
</script>

<template>
  <div class="min-h-screen bg-slate-50 text-slate-900 dark:bg-slate-950 dark:text-slate-50">
    <NavBar />
    <ToastContainer />

    <div class="mx-auto max-w-7xl px-4 pt-4 sm:px-6 lg:px-8">
      <TrialBanner
        v-if="auth.user"
        :subscription-status="auth.user.subscription_status"
        :trial-ends-at="auth.user.trial_ends_at"
      />
    </div>

    <main id="page-main" class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
      <RouterView />
    </main>

    <OnboardingTour
      v-model="showTour"
      :steps="TOUR_STEPS"
      @complete="handleTourComplete"
      @skip="handleTourSkip"
    />
  </div>
</template>
