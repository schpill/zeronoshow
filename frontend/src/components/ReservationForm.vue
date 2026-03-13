<script setup lang="ts">
import { reactive, ref } from 'vue'

import ReliabilityBadge from '@/components/ReliabilityBadge.vue'
import { useReservations } from '@/composables/useReservations'
import type { CustomerLookupResponse } from '@/types/reservations'

const emit = defineEmits<{
  created: [reservation: { id: string }]
}>()

const { createReservation, lookupCustomer, loading, errors } = useReservations()

const form = reactive({
  customer_name: '',
  phone: '',
  scheduled_at: '',
  guests: 2,
  notes: '',
  phone_verified: false,
})

const fieldErrors = ref<Record<string, string[]>>({})
const generalError = ref<string | null>(null)
const customerLookup = ref<CustomerLookupResponse | null>(null)

function resetForm() {
  form.customer_name = ''
  form.phone = ''
  form.scheduled_at = ''
  form.guests = 2
  form.notes = ''
  form.phone_verified = false
  customerLookup.value = null
  fieldErrors.value = {}
  generalError.value = null
}

async function handleLookup() {
  if (!form.phone) {
    customerLookup.value = null
    return
  }

  customerLookup.value = await lookupCustomer(form.phone)
}

async function handleSubmit() {
  fieldErrors.value = {}
  generalError.value = null

  try {
    const response = await createReservation({
      customer_name: form.customer_name,
      phone: form.phone,
      scheduled_at: form.scheduled_at,
      guests: Number(form.guests),
      notes: form.notes,
      phone_verified: form.phone_verified,
    })

    emit('created', response.reservation)
    resetForm()
  } catch (error) {
    if (typeof error === 'object' && error !== null && 'status' in error) {
      const status = Reflect.get(error, 'status')
      const data = Reflect.get(error, 'data')
      if (status === 422 && typeof data === 'object' && data !== null && 'errors' in data) {
        fieldErrors.value = Reflect.get(data, 'errors') as Record<string, string[]>
        return
      }
    }

    generalError.value = errors.create.value
  }
}
</script>

<template>
  <section
    id="reservation-form"
    class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900"
  >
    <div class="border-b border-slate-100 px-6 py-5 dark:border-slate-800">
      <p class="text-overline">Réservation rapide</p>
      <h2 class="text-heading-3 dark:text-slate-50">Nouvelle réservation</h2>
      <p class="text-body-sm mt-2 dark:text-slate-400">
        Saisie pensée pour être complétée en moins d’une minute.
      </p>
    </div>

    <form class="grid gap-5 px-6 py-6" @submit.prevent="handleSubmit">
      <div class="grid gap-5 md:grid-cols-2">
        <div>
          <label for="customer_name" class="text-label dark:text-slate-200">Client</label>
          <input
            id="customer_name"
            v-model="form.customer_name"
            type="text"
            class="mt-2 input-field"
            placeholder="Marc Dubois"
          />
          <p v-if="fieldErrors.customer_name" class="mt-2 text-sm text-red-700 dark:text-red-300">
            {{ fieldErrors.customer_name[0] }}
          </p>
        </div>

        <div>
          <label for="phone" class="text-label dark:text-slate-200">Téléphone</label>
          <input
            id="phone"
            v-model="form.phone"
            type="tel"
            class="mt-2 input-field"
            placeholder="+33612345678"
            @blur="handleLookup"
          />
          <p v-if="fieldErrors.phone" class="mt-2 text-sm text-red-700 dark:text-red-300">
            {{ fieldErrors.phone[0] }}
          </p>
        </div>

        <div>
          <label for="scheduled_at" class="text-label dark:text-slate-200">Date et heure</label>
          <input
            id="scheduled_at"
            v-model="form.scheduled_at"
            type="datetime-local"
            class="mt-2 input-field"
          />
          <p v-if="fieldErrors.scheduled_at" class="mt-2 text-sm text-red-700 dark:text-red-300">
            {{ fieldErrors.scheduled_at[0] }}
          </p>
        </div>

        <div>
          <label for="guests" class="text-label dark:text-slate-200">Couverts</label>
          <input id="guests" v-model="form.guests" min="1" type="number" class="mt-2 input-field" />
        </div>
      </div>

      <div>
        <label for="notes" class="text-label dark:text-slate-200">Notes</label>
        <textarea
          id="notes"
          v-model="form.notes"
          rows="4"
          class="mt-2 input-field"
          placeholder="Allergies, table, occasion spéciale..."
        />
      </div>

      <div
        class="flex flex-wrap items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-950"
      >
        <label
          class="flex items-center gap-3 text-sm font-medium text-slate-700 dark:text-slate-200"
        >
          <input
            id="phone_verified"
            v-model="form.phone_verified"
            type="checkbox"
            class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
          />
          Number confirmed by phone call
        </label>

        <ReliabilityBadge
          v-if="customerLookup?.found"
          :score="customerLookup.reliability_score"
          :tier="customerLookup.score_tier"
        />

        <span v-else-if="loading.lookup.value" class="text-caption dark:text-slate-400">
          Recherche du score...
        </span>
      </div>

      <p
        v-if="generalError"
        class="rounded-2xl bg-red-100 px-4 py-3 text-sm text-red-800 dark:bg-red-900/20 dark:text-red-300"
      >
        {{ generalError }}
      </p>

      <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-caption dark:text-slate-400">
          Le SMS de confirmation sera envoyé automatiquement si le numéro n’a pas été vérifié.
        </p>
        <button
          type="submit"
          class="inline-flex items-center justify-center rounded-2xl bg-emerald-500 px-5 py-3 text-sm font-semibold text-white transition hover:bg-emerald-600 disabled:cursor-not-allowed disabled:opacity-70"
          :disabled="loading.create.value"
        >
          {{ loading.create.value ? 'Enregistrement...' : 'Créer la réservation' }}
        </button>
      </div>
    </form>
  </section>
</template>
