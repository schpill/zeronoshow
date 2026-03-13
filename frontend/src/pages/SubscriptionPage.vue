<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'

import AppLayout from '@/layouts/AppLayout.vue'
import ErrorMessage from '@/components/ErrorMessage.vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'
import { useSubscription } from '@/composables/useSubscription'
import { useToast } from '@/composables/useToast'

const route = useRoute()
const { subscription, loading, fetchSubscription, createCheckoutSession } = useSubscription()
const pageError = ref<string | null>(null)
const toast = useToast()

async function loadSubscription() {
  pageError.value = null

  try {
    await fetchSubscription()
  } catch (error) {
    pageError.value = error instanceof Error ? error.message : 'Impossible de charger l abonnement.'
  }
}

onMounted(async () => {
  await loadSubscription()

  if (route.query.status === 'success') {
    toast.success('Abonnement activé.')
  }

  if (route.query.status === 'cancelled') {
    toast.warning('Le paiement a été annulé.')
  }
})

const statusLabel = computed(() => subscription.value?.subscription_status ?? 'trial')

async function handleCheckout() {
  const response = await createCheckoutSession()
  window.location.assign(response.checkout_url)
}
</script>

<template>
  <AppLayout>
    <div
      v-if="loading && !subscription && !pageError"
      class="flex min-h-[240px] items-center justify-center"
    >
      <LoadingSpinner size="lg" label="Chargement de l abonnement" />
    </div>

    <ErrorMessage
      v-else-if="pageError"
      title="Impossible de charger l abonnement"
      :message="pageError"
      @retry="loadSubscription"
    />

    <section
      v-else
      class="mx-auto max-w-3xl rounded-[32px] border border-slate-200 bg-white p-8 dark:border-slate-800 dark:bg-slate-900"
    >
      <p class="text-overline">Abonnement</p>
      <h1 class="text-heading-2 mt-2 dark:text-slate-50">Pilotage de la facturation</h1>
      <div class="mt-6 grid gap-4 md:grid-cols-3">
        <article
          class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950"
        >
          <p class="text-caption">Statut</p>
          <p class="mt-2 text-heading-4 dark:text-slate-50">{{ statusLabel }}</p>
        </article>
        <article
          class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950"
        >
          <p class="text-caption">Fin d’essai</p>
          <p class="mt-2 text-heading-4 dark:text-slate-50">
            {{ subscription?.trial_ends_at ?? '—' }}
          </p>
        </article>
        <article
          class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950"
        >
          <p class="text-caption">SMS ce mois</p>
          <p class="mt-2 text-heading-4 dark:text-slate-50">
            {{ subscription?.sms_cost_this_month ?? 0 }} €
          </p>
        </article>
      </div>
      <button
        type="button"
        class="mt-8 rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-emerald-700"
        @click="handleCheckout"
      >
        S’abonner
      </button>
    </section>
  </AppLayout>
</template>
