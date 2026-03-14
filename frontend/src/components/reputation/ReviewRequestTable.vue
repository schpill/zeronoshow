<script setup lang="ts">
import ReviewPlatformBadge from '@/components/reputation/ReviewPlatformBadge.vue'
import type { ReviewRequest } from '@/api/crm'

defineProps<{
  requests: ReviewRequest[]
}>()

function statusLabel(status: ReviewRequest['status']) {
  return {
    pending: 'En attente',
    sent: 'Envoyé',
    clicked: 'Cliqué',
    expired: 'Expiré',
  }[status]
}
</script>

<template>
  <section class="rounded-[32px] border border-slate-200 bg-white p-6">
    <div class="mb-4">
      <p class="text-overline">Historique des demandes</p>
      <h2 class="text-heading-3">Demandes envoyées</h2>
    </div>

    <p
      v-if="requests.length === 0"
      class="rounded-2xl border border-dashed border-slate-200 px-4 py-8 text-center text-sm text-slate-500"
    >
      Aucune demande envoyée
    </p>

    <table v-else class="min-w-full text-left text-sm">
      <thead class="text-slate-500">
        <tr>
          <th class="pb-3">Client</th>
          <th class="pb-3">Plateforme</th>
          <th class="pb-3">Statut</th>
          <th class="pb-3">Envoyé</th>
          <th class="pb-3">Cliqué</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="request in requests" :key="request.id" class="border-t border-slate-100">
          <td class="py-3">{{ request.customer_name }}</td>
          <td class="py-3"><ReviewPlatformBadge :platform="request.platform" /></td>
          <td class="py-3">{{ statusLabel(request.status) }}</td>
          <td class="py-3">{{ request.sent_at ?? '—' }}</td>
          <td class="py-3">{{ request.clicked_at ?? '—' }}</td>
        </tr>
      </tbody>
    </table>
  </section>
</template>
