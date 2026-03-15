<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue'

import { adminApiClient } from '@/api/adminAxios'
import HealthIndicator from '@/components/admin/HealthIndicator.vue'
import StatCard from '@/components/admin/StatCard.vue'

const stats = ref({
  total_businesses: 0,
  active_trials: 0,
  expired_trials: 0,
  paid_subscriptions: 0,
  cancelled_subscriptions: 0,
  sms_sent_this_month: 0,
  sms_cost_this_month: 0,
  failed_jobs_count: 0,
})

const health = ref({
  queue_worker_running: false,
  failed_jobs_count: 0,
  redis_ping: false,
  last_twilio_webhook_at: null as string | null,
  database_ok: false,
})

let timer: number | undefined

async function loadDashboard() {
  stats.value = await adminApiClient.get('/stats')
  health.value = await adminApiClient.get('/system/health')
}

onMounted(async () => {
  await loadDashboard()
  timer = window.setInterval(loadDashboard, 30000)
})

onBeforeUnmount(() => {
  if (timer) {
    window.clearInterval(timer)
  }
})
</script>

<template>
  <section class="space-y-8">
    <div>
      <p class="text-overline">Plateforme</p>
      <h2 class="text-heading-2 mt-2">Pilotage global</h2>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
      <StatCard label="Total businesses" :value="stats.total_businesses" color="blue" />
      <StatCard label="Active trials" :value="stats.active_trials" color="green" />
      <StatCard label="Expired trials" :value="stats.expired_trials" color="yellow" />
      <StatCard label="Paid subscriptions" :value="stats.paid_subscriptions" color="green" />
      <StatCard label="SMS this month" :value="stats.sms_sent_this_month" color="blue" />
      <StatCard label="SMS cost this month" :value="`${stats.sms_cost_this_month} €`" color="red" />
    </div>

    <div class="space-y-4">
      <div>
        <p class="text-overline">System health</p>
        <h3 class="text-heading-4 mt-2">Surveillance temps réel</h3>
      </div>
      <div class="grid gap-4 md:grid-cols-2">
        <HealthIndicator
          :status="health.queue_worker_running ? 'ok' : 'error'"
          label="Queue worker"
          :detail="
            health.queue_worker_running ? 'Worker heartbeat detected' : 'Worker heartbeat missing'
          "
        />
        <HealthIndicator
          :status="health.failed_jobs_count > 0 ? 'error' : 'ok'"
          label="Failed jobs"
          :detail="`${health.failed_jobs_count} jobs en échec`"
        />
        <HealthIndicator
          :status="health.redis_ping ? 'ok' : 'error'"
          label="Redis"
          :detail="health.redis_ping ? 'Connexion OK' : 'Connexion indisponible'"
        />
        <HealthIndicator
          :status="health.last_twilio_webhook_at ? 'ok' : 'warning'"
          label="Last webhook"
          :detail="health.last_twilio_webhook_at ?? 'Aucun webhook reçu'"
        />
      </div>
    </div>
  </section>
</template>
