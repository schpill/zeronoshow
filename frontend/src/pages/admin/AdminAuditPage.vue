<script setup lang="ts">
import { onMounted, ref } from 'vue'

import { adminApiClient } from '@/api/adminAxios'

interface AdminAuditLogItem {
  id: string
  created_at: string
  admin_name: string | null
  action: string
  target_type: string
  target_id: string
  payload: Record<string, unknown> | null
}

interface AdminAuditLogListResponse {
  data: AdminAuditLogItem[]
  meta: {
    current_page: number
    last_page: number
    total: number
  }
}

const action = ref('')
const dateFrom = ref('')
const dateTo = ref('')
const page = ref(1)
const logs = ref<AdminAuditLogItem[]>([])
const meta = ref({ current_page: 1, last_page: 1, total: 0 })

async function loadLogs(nextPage = 1) {
  page.value = nextPage

  const search = new URLSearchParams()
  if (action.value) search.set('action', action.value)
  if (dateFrom.value) search.set('date_from', dateFrom.value)
  if (dateTo.value) search.set('date_to', dateTo.value)
  if (page.value > 1) search.set('page', String(page.value))

  const suffix = search.toString()
  const response = await adminApiClient.get<AdminAuditLogListResponse>(
    `/audit-logs${suffix ? `?${suffix}` : ''}`,
  )
  logs.value = response.data
  meta.value = response.meta
}

function applyFilters() {
  return loadLogs(1)
}

onMounted(() => {
  void loadLogs()
})
</script>

<template>
  <section class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
      <div>
        <p class="text-overline">Audit</p>
        <h2 class="text-heading-2 mt-2">Journal admin</h2>
      </div>

      <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <select v-model="action" class="input-field">
          <option value="">Toutes les actions</option>
          <option value="extend_trial">extend_trial</option>
          <option value="cancel_subscription">cancel_subscription</option>
          <option value="impersonate">impersonate</option>
        </select>
        <input v-model="dateFrom" type="date" class="input-field" />
        <input v-model="dateTo" type="date" class="input-field" />
        <button
          data-testid="apply-audit-filters"
          class="rounded-2xl bg-slate-900 px-4 py-2 text-button text-white"
          @click="applyFilters"
        >
          Filtrer
        </button>
      </div>
    </div>

    <div class="overflow-hidden rounded-[28px] border border-slate-200 bg-white">
      <table class="min-w-full divide-y divide-slate-200">
        <thead class="bg-slate-50">
          <tr>
            <th class="px-4 py-3 text-left text-label">Date</th>
            <th class="px-4 py-3 text-left text-label">Admin</th>
            <th class="px-4 py-3 text-left text-label">Action</th>
            <th class="px-4 py-3 text-left text-label">Target</th>
            <th class="px-4 py-3 text-left text-label">Payload</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="log in logs" :key="log.id">
            <td class="px-4 py-3 text-body-sm">{{ log.created_at }}</td>
            <td class="px-4 py-3 text-body-sm">{{ log.admin_name ?? '—' }}</td>
            <td class="px-4 py-3 text-body-sm">{{ log.action }}</td>
            <td class="px-4 py-3 text-body-sm">
              <RouterLink
                v-if="log.target_type === 'Business'"
                :to="`/admin/businesses/${log.target_id}`"
                class="text-emerald-700 underline decoration-slate-300 underline-offset-4"
              >
                {{ log.payload?.business_name ?? log.target_id }}
              </RouterLink>
              <span v-else>{{ log.target_type }} · {{ log.target_id }}</span>
            </td>
            <td class="px-4 py-3 text-body-sm">{{ JSON.stringify(log.payload) }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div
      class="flex items-center justify-between rounded-[28px] border border-slate-200 bg-white p-4"
    >
      <p class="text-body-sm text-slate-600">
        Page {{ meta.current_page }} / {{ meta.last_page }} · {{ meta.total }} entrées
      </p>
      <div class="flex gap-3">
        <button
          class="rounded-2xl border border-slate-200 px-4 py-2 text-button disabled:opacity-50"
          :disabled="meta.current_page <= 1"
          @click="loadLogs(meta.current_page - 1)"
        >
          Précédent
        </button>
        <button
          data-testid="audit-next-page"
          class="rounded-2xl border border-slate-200 px-4 py-2 text-button disabled:opacity-50"
          :disabled="meta.current_page >= meta.last_page"
          @click="loadLogs(meta.current_page + 1)"
        >
          Suivant
        </button>
      </div>
    </div>
  </section>
</template>
