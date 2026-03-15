<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'

import { adminApiClient } from '@/api/adminAxios'

interface BusinessRow {
  id: string
  name: string
  email: string
  subscription_status: string
  trial_ends_at: string | null
  reservations_count: number
  sms_sent_count: number
  created_at: string | null
}

const router = useRouter()
const search = ref('')
const status = ref('')
const page = ref(1)
const businesses = ref<BusinessRow[]>([])
const total = ref(0)
let debounceTimer: number | undefined

async function loadBusinesses() {
  const params = new URLSearchParams()
  if (search.value) params.set('search', search.value)
  if (status.value) params.set('status', status.value)
  params.set('page', String(page.value))

  const response = await adminApiClient.get<{ data: BusinessRow[]; meta: { total: number } }>(
    `/businesses?${params.toString()}`,
  )

  businesses.value = response.data
  total.value = response.meta.total
}

function goToBusiness(id: string) {
  router.push(`/admin/businesses/${id}`)
}

function previousPage() {
  if (page.value === 1) {
    return
  }

  page.value -= 1
  void loadBusinesses()
}

function nextPage() {
  if (businesses.value.length === 0 || businesses.value.length * page.value >= total.value) {
    return
  }

  page.value += 1
  void loadBusinesses()
}

watch([search, status], () => {
  window.clearTimeout(debounceTimer)
  debounceTimer = window.setTimeout(() => {
    page.value = 1
    void loadBusinesses()
  }, 300)
})

onMounted(loadBusinesses)
</script>

<template>
  <section class="space-y-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
      <div>
        <p class="text-overline">Businesses</p>
        <h2 class="text-heading-2 mt-2">Établissements</h2>
      </div>
      <div class="grid gap-3 md:grid-cols-2">
        <input v-model="search" type="search" placeholder="Rechercher" class="input-field" />
        <select v-model="status" class="input-field">
          <option value="">All</option>
          <option value="trial">Trial</option>
          <option value="active">Active</option>
          <option value="cancelled">Cancelled</option>
        </select>
      </div>
    </div>

    <div class="overflow-hidden rounded-[28px] border border-slate-200 bg-white">
      <table class="min-w-full divide-y divide-slate-200">
        <thead class="bg-slate-50">
          <tr>
            <th class="px-4 py-3 text-left text-label">Name</th>
            <th class="px-4 py-3 text-left text-label">Email</th>
            <th class="px-4 py-3 text-left text-label">Status</th>
            <th class="px-4 py-3 text-left text-label">Trial ends</th>
            <th class="px-4 py-3 text-left text-label">Reservations</th>
            <th class="px-4 py-3 text-left text-label">SMS</th>
            <th class="px-4 py-3 text-left text-label">Created at</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr
            v-for="business in businesses"
            :key="business.id"
            class="cursor-pointer hover:bg-slate-50"
            @click="goToBusiness(business.id)"
          >
            <td class="px-4 py-3 text-body-sm">{{ business.name }}</td>
            <td class="px-4 py-3 text-body-sm">{{ business.email }}</td>
            <td class="px-4 py-3 text-body-sm">{{ business.subscription_status }}</td>
            <td class="px-4 py-3 text-body-sm">{{ business.trial_ends_at ?? '—' }}</td>
            <td class="px-4 py-3 text-body-sm">{{ business.reservations_count }}</td>
            <td class="px-4 py-3 text-body-sm">{{ business.sms_sent_count }}</td>
            <td class="px-4 py-3 text-body-sm">{{ business.created_at ?? '—' }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="flex items-center justify-between">
      <button
        class="rounded-2xl border border-slate-200 px-4 py-2 text-button"
        :disabled="page === 1"
        @click="previousPage"
      >
        Previous
      </button>
      <p class="text-caption">Page {{ page }} · {{ total }} businesses</p>
      <button
        class="rounded-2xl border border-slate-200 px-4 py-2 text-button"
        :disabled="businesses.length === 0 || businesses.length * page >= total"
        @click="nextPage"
      >
        Next
      </button>
    </div>
  </section>
</template>
