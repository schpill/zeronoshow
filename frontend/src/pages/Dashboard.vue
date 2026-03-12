<!-- eslint-disable vue/multi-word-component-names -->
<script setup lang="ts">
import { ref } from 'vue'

import AppLayout from '@/layouts/AppLayout.vue'
import ReservationForm from '@/components/ReservationForm.vue'

const createdMessage = ref<string | null>(null)

function handleCreated() {
  createdMessage.value = 'Réservation créée avec succès.'
}
</script>

<template>
  <AppLayout>
    <section class="mb-6 grid gap-4 rounded-[32px] border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900">
      <div class="grid gap-6 lg:grid-cols-[1.2fr_0.8fr] lg:items-end">
        <div>
          <p class="text-overline">Dashboard</p>
          <h1 class="text-heading-1 mt-2 dark:text-slate-50">Réservations</h1>
          <p class="text-body mt-4 max-w-2xl dark:text-slate-300">
            Créez une réservation, envoyez le lien de confirmation par SMS et suivez la fiabilité client depuis une interface inspirée du template backoffice.
          </p>
        </div>
        <div class="grid grid-cols-3 gap-3">
          <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950">
            <p class="text-caption dark:text-slate-400">Aujourd’hui</p>
            <p class="mt-2 text-3xl font-bold text-slate-900 dark:text-slate-50">7</p>
          </div>
          <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950">
            <p class="text-caption dark:text-slate-400">Confirmées</p>
            <p class="mt-2 text-3xl font-bold text-emerald-600 dark:text-emerald-400">4</p>
          </div>
          <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950">
            <p class="text-caption dark:text-slate-400">À vérifier</p>
            <p class="mt-2 text-3xl font-bold text-slate-700 dark:text-slate-200">2</p>
          </div>
        </div>
      </div>
    </section>

    <p
      v-if="createdMessage"
      class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 dark:border-emerald-900/50 dark:bg-emerald-900/20 dark:text-emerald-300"
    >
      {{ createdMessage }}
    </p>

    <ReservationForm @created="handleCreated" />
  </AppLayout>
</template>
