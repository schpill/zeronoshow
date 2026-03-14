<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'

import AppLayout from '@/layouts/AppLayout.vue'
import CustomerCrmPanel from '@/components/crm/CustomerCrmPanel.vue'
import CustomerVipBadge from '@/components/crm/CustomerVipBadge.vue'
import { useCustomerCrm } from '@/composables/useCustomerCrm'
import type { ReservationCustomer } from '@/types/reservations'

const crm = useCustomerCrm()
const selectedCustomer = ref<ReservationCustomer | null>(null)

const filters = reactive({
  is_vip: false,
  is_blacklisted: false,
  birthday_month: false,
})

const month = new Date().getMonth() + 1

async function loadCustomers() {
  await crm.fetchCustomers({
    is_vip: filters.is_vip,
    is_blacklisted: filters.is_blacklisted,
    birthday_month: filters.birthday_month ? month : null,
  })
}

onMounted(() => {
  void loadCustomers()
})

const title = computed(() => `${crm.customers.value.length} clients suivis`)

function handleUpdated(updatedCustomer: ReservationCustomer) {
  selectedCustomer.value = updatedCustomer
}
</script>

<template>
  <AppLayout>
    <section class="mb-6 rounded-[32px] border border-slate-200 bg-white p-6">
      <p class="text-overline">Phase 9</p>
      <h1 class="mt-2 text-heading-2">Clients</h1>
      <p class="mt-3 text-sm text-slate-600">{{ title }}</p>
    </section>

    <section class="mb-6 flex flex-wrap gap-3 rounded-[28px] border border-slate-200 bg-white p-4">
      <label class="flex items-center gap-2 rounded-full border border-slate-200 px-3 py-2 text-sm">
        <input v-model="filters.is_vip" type="checkbox" @change="loadCustomers" />
        VIP
      </label>
      <label class="flex items-center gap-2 rounded-full border border-slate-200 px-3 py-2 text-sm">
        <input v-model="filters.is_blacklisted" type="checkbox" @change="loadCustomers" />
        Liste noire
      </label>
      <label class="flex items-center gap-2 rounded-full border border-slate-200 px-3 py-2 text-sm">
        <input v-model="filters.birthday_month" type="checkbox" @change="loadCustomers" />
        Anniversaire ce mois
      </label>
    </section>

    <section class="grid gap-4">
      <article
        v-for="customer in crm.customers.value"
        :key="customer.id"
        class="rounded-[28px] border border-slate-200 bg-white p-5"
      >
        <div class="flex flex-wrap items-start justify-between gap-4">
          <div>
            <div class="flex items-center gap-2">
              <p class="text-heading-4">{{ customer.phone }}</p>
              <CustomerVipBadge :is-vip="Boolean(customer.is_vip)" />
            </div>
            <p class="mt-2 text-sm text-slate-500">
              {{ customer.reservations_count }} réservations · {{ customer.shows_count }} présences
            </p>
          </div>
          <button
            type="button"
            class="rounded-2xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white"
            @click="selectedCustomer = customer"
          >
            Fiche client
          </button>
        </div>
      </article>
    </section>

    <CustomerCrmPanel
      v-if="selectedCustomer"
      :customer="selectedCustomer"
      :open="Boolean(selectedCustomer)"
      @close="selectedCustomer = null"
      @updated="handleUpdated"
    />
  </AppLayout>
</template>
