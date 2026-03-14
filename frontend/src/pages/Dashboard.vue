<!-- eslint-disable vue/multi-word-component-names -->
<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'

import AppLayout from '@/layouts/AppLayout.vue'
import DateNavigator from '@/components/DateNavigator.vue'
import ErrorMessage from '@/components/ErrorMessage.vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'
import ReservationForm from '@/components/ReservationForm.vue'
import ReservationList from '@/components/ReservationList.vue'
import ReservationRow from '@/components/ReservationRow.vue'
import StatsBar from '@/components/StatsBar.vue'
import { usePolling } from '@/composables/usePolling'
import { useReservations } from '@/composables/useReservations'
import { useToast } from '@/composables/useToast'
import type { LeoMessageActivity } from '@/types/leo'
import type { DashboardStats, ReservationRecord } from '@/types/reservations'

const selectedDate = ref(new Date().toISOString().slice(0, 10))
const viewMode = ref<'day' | 'week'>('day')
const sourceFilter = ref<string | null>(null)
const reservations = ref<ReservationRecord[]>([])
const pageError = ref<string | null>(null)
const stats = ref<DashboardStats>({
  confirmed: 0,
  pending_verification: 0,
  pending_reminder: 0,
  cancelled: 0,
  no_show: 0,
  show: 0,
  total: 0,
})
const smsCostThisMonth = ref(0)
const weeklyNoShowRate = ref<number | null>(null)
const leoActivity = ref<LeoMessageActivity[]>([])
const { fetchDashboard, loading } = useReservations()
const toast = useToast()

async function refreshReservations() {
  pageError.value = null

  try {
    const date = selectedDate.value
    const week = viewMode.value === 'week' ? toIsoWeek(date) : undefined
    const source = sourceFilter.value ?? undefined
    const response = await fetchDashboard({ date, week, source })
    reservations.value = response.reservations
    stats.value = response.stats
    smsCostThisMonth.value = response.sms_cost_this_month
    weeklyNoShowRate.value = response.weekly_no_show_rate
    leoActivity.value = response.leo_activity ?? []
  } catch (error) {
    pageError.value = error instanceof Error ? error.message : 'Impossible de charger le dashboard.'
  }
}

usePolling(refreshReservations, 30_000)

watch(selectedDate, () => {
  void refreshReservations()
})

function handleCreated(reservation: ReservationRecord) {
  toast.success('Réservation créée avec succès.')
  reservations.value = [...reservations.value, reservation]
  stats.value = {
    ...stats.value,
    total: stats.value.total + 1,
  }
}

function handleUpdated(updatedReservation: ReservationRecord) {
  reservations.value = reservations.value.map((reservation) =>
    reservation.id === updatedReservation.id ? updatedReservation : reservation,
  )
}

function switchView(mode: 'day' | 'week') {
  viewMode.value = mode
  void refreshReservations()
}

function toIsoWeek(date: string) {
  const value = new Date(`${date}T12:00:00`)
  const day = (value.getDay() + 6) % 7
  value.setDate(value.getDate() - day + 3)
  const firstThursday = new Date(value.getFullYear(), 0, 4)
  const firstDay = (firstThursday.getDay() + 6) % 7
  firstThursday.setDate(firstThursday.getDate() - firstDay + 3)
  const week = 1 + Math.round((value.getTime() - firstThursday.getTime()) / 604800000)
  return `${value.getFullYear()}-W${String(week).padStart(2, '0')}`
}

const summary = computed(
  () => `${smsCostThisMonth.value.toFixed(2)} € SMS · ${weeklyNoShowRate.value ?? 0}% no-show`,
)

const groupedReservations = computed(() => {
  if (viewMode.value !== 'week') {
    return []
  }

  const groups = new Map<string, ReservationRecord[]>()

  reservations.value.forEach((reservation) => {
    const key = new Date(reservation.scheduled_at).toISOString().slice(0, 10)
    groups.set(key, [...(groups.get(key) ?? []), reservation])
  })

  return Array.from(groups.entries()).map(([date, items]) => ({ date, items }))
})
</script>

<template>
  <AppLayout>
    <div
      v-if="loading.fetch.value && reservations.length === 0 && !pageError"
      class="flex min-h-[240px] items-center justify-center"
    >
      <LoadingSpinner size="lg" label="Chargement du dashboard" />
    </div>

    <ErrorMessage
      v-else-if="pageError"
      title="Impossible de charger le dashboard"
      :message="pageError"
      @retry="refreshReservations"
    />

    <template v-else>
      <section
        class="mb-6 grid gap-4 rounded-[32px] border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900"
      >
        <div class="grid gap-6 lg:grid-cols-[1.2fr_0.8fr] lg:items-end">
          <div>
            <p class="text-overline">Dashboard</p>
            <h1 class="text-heading-1 mt-2 dark:text-slate-50">Réservations</h1>
            <p class="text-body mt-4 max-w-2xl dark:text-slate-300">
              Vue journée ou semaine, coût SMS et suivi opérationnel complet.
            </p>
          </div>
          <div class="flex flex-col items-start gap-3 lg:items-end">
            <DateNavigator v-model="selectedDate" />
            <div class="flex gap-2">
              <button
                type="button"
                class="rounded-xl px-4 py-2 text-sm font-semibold"
                :class="
                  viewMode === 'day'
                    ? 'bg-emerald-500 text-white'
                    : 'border border-slate-200 text-slate-700 dark:border-slate-700 dark:text-slate-200'
                "
                @click="switchView('day')"
              >
                Jour
              </button>
              <button
                type="button"
                class="rounded-xl px-4 py-2 text-sm font-semibold"
                :class="
                  viewMode === 'week'
                    ? 'bg-emerald-500 text-white'
                    : 'border border-slate-200 text-slate-700 dark:border-slate-700 dark:text-slate-200'
                "
                @click="switchView('week')"
              >
                Semaine
              </button>
            </div>
            <p class="text-caption dark:text-slate-400">{{ summary }}</p>
          </div>
        </div>
      </section>

      <StatsBar :stats="stats" />

      <div class="flex items-center gap-2">
        <label class="text-sm text-slate-500">Source :</label>
        <select
          v-model="sourceFilter"
          class="rounded-xl border border-slate-200 px-3 py-1.5 text-sm text-slate-700"
          @change="void refreshReservations()"
        >
          <option :value="null">Toutes</option>
          <option value="widget">Widget</option>
          <option value="manual">Manuel</option>
        </select>
      </div>

      <div class="grid gap-6 xl:grid-cols-[0.95fr_1.05fr]">
        <div class="grid gap-6">
          <ReservationForm @created="handleCreated" />
          <section
            v-if="leoActivity.length > 0"
            class="rounded-[32px] border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900"
          >
            <div class="flex items-center justify-between gap-3">
              <div>
                <p class="text-overline">Activité Léo</p>
                <h2 class="text-heading-4 dark:text-slate-50">3 derniers messages</h2>
              </div>
              <RouterLink
                to="/leo"
                class="text-sm font-semibold text-emerald-700 dark:text-emerald-400"
              >
                Voir Léo →
              </RouterLink>
            </div>
            <div class="mt-4 grid gap-3">
              <article
                v-for="message in leoActivity"
                :key="message.id"
                class="rounded-2xl border border-slate-200 px-4 py-3 dark:border-slate-800"
              >
                <div class="flex items-center justify-between gap-3">
                  <p class="text-sm font-semibold text-slate-900 dark:text-slate-50">
                    {{ message.direction === 'inbound' ? 'Entrant' : 'Sortant' }}
                    <span class="text-slate-400">· {{ message.intent ?? 'conversation' }}</span>
                  </p>
                  <p class="text-caption dark:text-slate-400">{{ message.created_at ?? '—' }}</p>
                </div>
                <p class="mt-2 text-body-sm dark:text-slate-400">
                  {{ message.response_preview ?? 'Aucun aperçu disponible.' }}
                </p>
              </article>
            </div>
          </section>
        </div>

        <ReservationList
          v-if="viewMode === 'day'"
          :reservations="reservations"
          :loading="loading.fetch.value"
          @updated="handleUpdated"
        />
        <section
          v-else
          class="grid gap-4 rounded-[32px] border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900"
        >
          <div
            v-for="group in groupedReservations"
            :key="group.date"
            class="grid gap-3 rounded-2xl border border-slate-200 p-4 dark:border-slate-800"
          >
            <h2 class="text-heading-4 capitalize dark:text-slate-50">
              {{
                new Intl.DateTimeFormat('fr-FR', {
                  weekday: 'long',
                  day: 'numeric',
                  month: 'long',
                }).format(new Date(`${group.date}T12:00:00`))
              }}
            </h2>
            <ReservationRow
              v-for="reservation in group.items"
              :key="reservation.id"
              :reservation="reservation"
              @updated="handleUpdated"
            />
          </div>
        </section>
      </div>
    </template>
  </AppLayout>
</template>
