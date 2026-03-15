<script setup lang="ts">
import { RouterView } from 'vue-router'

import NavBar from '@/components/NavBar.vue'
import ToastContainer from '@/components/ToastContainer.vue'
import TrialBanner from '@/components/TrialBanner.vue'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
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
  </div>
</template>
