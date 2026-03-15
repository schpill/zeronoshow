<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'

import { adminApiClient } from '@/api/adminAxios'
import ImpersonateModal from '@/components/admin/ImpersonateModal.vue'

interface BusinessSummary {
  id: string
  name: string
  email: string
  phone: string | null
  subscription_status: string
  trial_ends_at: string | null
  created_at: string | null
}

interface ReservationSummary {
  id: string
  customer_name: string
  status: string
  scheduled_at: string
}

interface SmsLogSummary {
  total_sent: number
  delivered: number
  failed: number
  cost: number | string
}

interface AdminBusinessDetailResponse {
  business: BusinessSummary
  recent_reservations: ReservationSummary[]
  sms_log_summary: SmsLogSummary
  subscription_history: Array<{
    subscription_status: string
    trial_ends_at: string | null
    stripe_customer_id: string | null
    stripe_subscription_id: string | null
  }>
}

interface ImpersonationResponse {
  impersonation_token: string
}

const route = useRoute()

const detail = ref<AdminBusinessDetailResponse | null>(null)
const extendDays = ref(7)
const cancelReason = ref('')
const impersonateOpen = ref(false)
const cancelOpen = ref(false)

async function loadDetail() {
  detail.value = await adminApiClient.get<AdminBusinessDetailResponse>(
    `/businesses/${route.params.id}`,
  )
}

async function extendTrial() {
  await adminApiClient.patch(`/businesses/${route.params.id}/extend-trial`, {
    days: extendDays.value,
  })
  await loadDetail()
}

async function cancelSubscription() {
  await adminApiClient.patch(`/businesses/${route.params.id}/cancel-subscription`, {
    reason: cancelReason.value,
  })
  cancelOpen.value = false
  await loadDetail()
}

async function confirmImpersonation() {
  const response = await adminApiClient.post<ImpersonationResponse>(
    `/businesses/${route.params.id}/impersonate`,
  )
  window.open(
    `/dashboard?impersonation_token=${response.impersonation_token}`,
    '_blank',
    'noopener',
  )
  impersonateOpen.value = false
}

onMounted(loadDetail)
</script>

<template>
  <section v-if="detail" class="space-y-6">
    <div class="rounded-[28px] border border-slate-200 bg-white p-6">
      <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
          <p class="text-overline">Business detail</p>
          <h2 class="text-heading-2 mt-2">{{ detail.business.name }}</h2>
          <div class="mt-3 space-y-1 text-body-sm text-slate-600">
            <p>{{ detail.business.email }}</p>
            <p>{{ detail.business.phone ?? 'Telephone indisponible' }}</p>
            <p>Created at: {{ detail.business.created_at ?? '—' }}</p>
          </div>
        </div>
        <div class="rounded-2xl bg-slate-900 px-4 py-3 text-white">
          <p class="text-label uppercase tracking-[0.24em]">Subscription</p>
          <p class="mt-2 text-heading-4">{{ detail.business.subscription_status }}</p>
          <p class="mt-1 text-body-sm text-slate-200">
            Trial ends: {{ detail.business.trial_ends_at ?? '—' }}
          </p>
        </div>
      </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
      <div class="rounded-[28px] border border-slate-200 bg-white p-6">
        <h3 class="text-heading-4">Interventions</h3>
        <div class="mt-4 space-y-4">
          <div class="flex gap-3">
            <input v-model="extendDays" type="number" min="1" max="90" class="input-field" />
            <button
              class="rounded-2xl bg-emerald-500 px-4 py-2 text-button text-white"
              @click="extendTrial"
            >
              Prolonger l'essai
            </button>
          </div>
          <textarea
            v-model="cancelReason"
            class="input-field min-h-28"
            placeholder="Motif d'annulation"
          />
          <button
            data-testid="open-cancel-modal"
            class="rounded-2xl bg-red-500 px-4 py-2 text-button text-white"
            @click="cancelOpen = true"
          >
            Annuler l'abonnement
          </button>
          <button
            data-testid="open-impersonation-modal"
            class="rounded-2xl border border-slate-200 px-4 py-2 text-button"
            @click="impersonateOpen = true"
          >
            Impersonner
          </button>
        </div>
      </div>

      <div class="rounded-[28px] border border-slate-200 bg-white p-6">
        <h3 class="text-heading-4">SMS summary</h3>
        <dl class="mt-4 space-y-3">
          <div class="flex justify-between">
            <dt class="text-body-sm">Total</dt>
            <dd class="text-body-sm">{{ detail.sms_log_summary.total_sent }}</dd>
          </div>
          <div class="flex justify-between">
            <dt class="text-body-sm">Delivered</dt>
            <dd class="text-body-sm">{{ detail.sms_log_summary.delivered }}</dd>
          </div>
          <div class="flex justify-between">
            <dt class="text-body-sm">Failed</dt>
            <dd class="text-body-sm">{{ detail.sms_log_summary.failed }}</dd>
          </div>
          <div class="flex justify-between">
            <dt class="text-body-sm">Cost</dt>
            <dd class="text-body-sm">{{ detail.sms_log_summary.cost }} €</dd>
          </div>
        </dl>
      </div>
    </div>

    <div class="rounded-[28px] border border-slate-200 bg-white p-6">
      <h3 class="text-heading-4">Subscription history</h3>
      <ul class="mt-4 space-y-3">
        <li
          v-for="entry in detail.subscription_history"
          :key="`${entry.subscription_status}-${entry.stripe_subscription_id ?? 'none'}`"
          class="rounded-2xl bg-slate-50 px-4 py-3 text-body-sm"
        >
          {{ entry.subscription_status }} · {{ entry.trial_ends_at ?? '—' }} ·
          {{ entry.stripe_customer_id ?? 'No customer' }} ·
          {{ entry.stripe_subscription_id ?? 'No subscription' }}
        </li>
      </ul>
    </div>

    <div class="rounded-[28px] border border-slate-200 bg-white p-6">
      <h3 class="text-heading-4">Recent reservations</h3>
      <ul class="mt-4 space-y-3">
        <li
          v-for="reservation in detail.recent_reservations"
          :key="reservation.id"
          class="rounded-2xl bg-slate-50 px-4 py-3 text-body-sm"
        >
          {{ reservation.customer_name }} · {{ reservation.status }} ·
          {{ reservation.scheduled_at }}
        </li>
      </ul>
    </div>

    <ImpersonateModal
      :open="impersonateOpen"
      :business-name="detail.business.name"
      @close="impersonateOpen = false"
      @confirm="confirmImpersonation"
    />

    <div
      v-if="cancelOpen"
      class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4"
    >
      <div class="w-full max-w-lg rounded-[28px] border border-slate-200 bg-white p-6 shadow-xl">
        <p class="text-overline">Cancellation</p>
        <h2 class="text-heading-4 mt-2">Confirmer l'annulation</h2>
        <p class="text-body-sm mt-3 text-slate-600">
          Cette action basculera l'abonnement en `cancelled` pour {{ detail.business.name }}.
        </p>
        <div class="mt-6 flex justify-end gap-3">
          <button
            class="rounded-2xl border border-slate-200 px-4 py-2 text-button"
            @click="cancelOpen = false"
          >
            Retour
          </button>
          <button
            data-testid="confirm-cancel"
            class="rounded-2xl bg-red-500 px-4 py-2 text-button text-white"
            @click="cancelSubscription"
          >
            Confirmer
          </button>
        </div>
      </div>
    </div>
  </section>
</template>
