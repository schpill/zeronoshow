<script setup lang="ts">
import { reactive, watch, ref } from 'vue'

import { useCustomerCrm } from '@/composables/useCustomerCrm'
import type { ReservationCustomer } from '@/types/reservations'

const props = defineProps<{
  customer: ReservationCustomer
  open: boolean
}>()

const emit = defineEmits<{
  close: []
  updated: [customer: ReservationCustomer]
}>()

const crm = useCustomerCrm()
const savedMessage = ref('')

const form = reactive({
  notes: '',
  is_vip: false,
  is_blacklisted: false,
  birthday_month: null as number | null,
  birthday_day: null as number | null,
  preferred_table_notes: '',
})

watch(
  () => [props.customer, props.open],
  () => {
    form.notes = props.customer.notes ?? ''
    form.is_vip = props.customer.is_vip ?? false
    form.is_blacklisted = props.customer.is_blacklisted ?? false
    form.birthday_month = props.customer.birthday_month ?? null
    form.birthday_day = props.customer.birthday_day ?? null
    form.preferred_table_notes = props.customer.preferred_table_notes ?? ''
    savedMessage.value = ''
  },
  { immediate: true },
)

async function save() {
  const updated = await crm.updateCustomerCrm(props.customer.id, {
    notes: form.notes || null,
    is_vip: form.is_vip,
    is_blacklisted: form.is_blacklisted,
    birthday_month: form.birthday_month,
    birthday_day: form.birthday_day,
    preferred_table_notes: form.preferred_table_notes || null,
  })

  savedMessage.value = 'Sauvegardé'
  emit('updated', updated)
}
</script>

<template>
  <div
    v-if="open"
    class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 px-4"
  >
    <div class="w-full max-w-2xl rounded-[32px] bg-white p-6 shadow-2xl">
      <div class="flex items-start justify-between gap-4">
        <div>
          <p class="text-overline">Fiche client</p>
          <h2 class="mt-2 text-heading-3">{{ customer.phone }}</h2>
        </div>
        <button type="button" class="text-sm font-semibold text-slate-500" @click="emit('close')">
          Fermer
        </button>
      </div>

      <div class="mt-6 grid gap-5">
        <div>
          <label for="crm-notes" class="text-label">Notes</label>
          <textarea
            id="crm-notes"
            v-model="form.notes"
            rows="4"
            class="mt-2 input-field"
            placeholder="Préférences, habitudes, points d’attention..."
          />
        </div>

        <div class="grid gap-4 md:grid-cols-2">
          <label class="flex items-center gap-3 rounded-2xl border border-slate-200 px-4 py-3">
            <input
              data-test="crm-vip-toggle"
              v-model="form.is_vip"
              type="checkbox"
              class="h-4 w-4"
            />
            Client VIP
          </label>
          <label class="flex items-center gap-3 rounded-2xl border border-slate-200 px-4 py-3">
            <input
              data-test="crm-blacklist-toggle"
              v-model="form.is_blacklisted"
              type="checkbox"
              class="h-4 w-4"
            />
            Liste noire
          </label>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
          <div>
            <label for="crm-birthday-month" class="text-label">Mois</label>
            <input
              id="crm-birthday-month"
              v-model="form.birthday_month"
              type="number"
              min="1"
              max="12"
              class="mt-2 input-field"
            />
          </div>
          <div>
            <label for="crm-birthday-day" class="text-label">Jour</label>
            <input
              id="crm-birthday-day"
              v-model="form.birthday_day"
              type="number"
              min="1"
              max="31"
              class="mt-2 input-field"
            />
          </div>
          <div>
            <label for="crm-preferred-table" class="text-label">Table</label>
            <input
              id="crm-preferred-table"
              v-model="form.preferred_table_notes"
              type="text"
              class="mt-2 input-field"
            />
          </div>
        </div>

        <p v-if="crm.error.value" class="text-sm text-red-700">{{ crm.error.value }}</p>
        <p v-if="savedMessage" class="text-sm font-semibold text-emerald-700">{{ savedMessage }}</p>

        <div class="flex justify-end gap-3">
          <button
            type="button"
            class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-semibold"
            @click="emit('close')"
          >
            Annuler
          </button>
          <button
            data-test="crm-save"
            type="button"
            class="rounded-2xl bg-emerald-600 px-4 py-3 text-sm font-semibold text-white"
            :disabled="crm.loading.value"
            @click="save"
          >
            Enregistrer
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
