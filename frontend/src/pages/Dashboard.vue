<!-- eslint-disable vue/multi-word-component-names -->
<script setup lang="ts">
import { computed, ref } from 'vue'

import AppLayout from '@/layouts/AppLayout.vue'
import DateNavigator from '@/components/DateNavigator.vue'
import ReservationForm from '@/components/ReservationForm.vue'
import ReservationList from '@/components/ReservationList.vue'
import ReservationRow from '@/components/ReservationRow.vue'
import StatsBar from '@/components/StatsBar.vue'
import { usePolling } from '@/composables/usePolling'
import { useReservations } from '@/composables/useReservations'
import type { DashboardStats, ReservationRecord } from '@/types/reservations'

const createdMessage = ref<string | null>(null)
const selectedDate = ref(new Date().toISOString().slice(0, 10))
const viewMode = ref<'day' | 'week'>('day')
const reservations = ref<ReservationRecord[]>([])
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
const { fetchDashboard, loading } = useReservations()

async function refreshReservations() {
  const date = selectedDate.value
  const week = viewMode.value === 'week' ? toIsoWeek(date) : undefined
  const response = await fetchDashboard({ date, week })
  reservations.value = response.reservations
  stats.value = response.stats
  smsCostThisMonth.value = response.sms_cost_this_month
  weeklyNoShowRate.value = response.weekly_no_show_rate
}

usePolling(refreshReservations, 30_000)

function handleCreated(reservation: ReservationRecord) {
  createdMessage.value = 'Réservation créée avec succès.'
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

    <p
      v-if="createdMessage"
      class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 dark:border-emerald-900/50 dark:bg-emerald-900/20 dark:text-emerald-300"
    >
      {{ createdMessage }}
    </p>

    <div class="grid gap-6 xl:grid-cols-[0.95fr_1.05fr]">
      <ReservationForm @created="handleCreated" />
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
  </AppLayout>
</template>
