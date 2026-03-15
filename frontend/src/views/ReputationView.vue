<script setup lang="ts">
import { onMounted } from 'vue'

import ReviewRequestTable from '@/components/reputation/ReviewRequestTable.vue'
import ReviewSettingsCard from '@/components/reputation/ReviewSettingsCard.vue'
import ReviewStatsBar from '@/components/reputation/ReviewStatsBar.vue'
import { useReviewSettings } from '@/composables/useReviewSettings'

const reputation = useReviewSettings()

async function loadReputation() {
  await Promise.all([
    reputation.fetchSettings(),
    reputation.fetchRequests(),
    reputation.fetchStats(),
  ])
}

onMounted(() => {
  void loadReputation()
})
</script>

<template>

    <section class="mb-6 rounded-[32px] border border-slate-200 bg-white p-6">
      <p class="text-overline">Phase 9</p>
      <h1 class="mt-2 text-heading-2">Réputation</h1>
      <p class="mt-3 max-w-2xl text-sm text-slate-600">
        Paramètres avis, historique des demandes et taux de clic post-visite.
      </p>
    </section>

    <div class="grid gap-6">
      <ReviewSettingsCard />
      <ReviewStatsBar :stats="reputation.stats.value" />
      <ReviewRequestTable :requests="reputation.requests.value" />
    </div>

</template>
