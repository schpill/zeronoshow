<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import type { CreateWaitlistEntryPayload } from '@/api/waitlist'
import { useWaitlist } from '@/composables/useWaitlist'
import WaitlistEntryRow from '@/components/waitlist/WaitlistEntryRow.vue'
import AddWaitlistEntryModal from '@/components/waitlist/AddWaitlistEntryModal.vue'
import WaitlistSettingsCard from '@/components/waitlist/WaitlistSettingsCard.vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'
import ErrorMessage from '@/components/ErrorMessage.vue'

const {
  entries,
  loading,
  error,
  fetchEntries,
  addEntry,
  removeEntry,
  notify,
  pendingCount,
  notifiedCount,
} = useWaitlist()

const slotDate = ref(new Date().toISOString().split('T')[0])
const showAddModal = ref(false)

const loadEntries = () => {
  fetchEntries({ slot_date: slotDate.value })
}

onMounted(loadEntries)
watch(slotDate, loadEntries)

const handleAddEntry = async (payload: CreateWaitlistEntryPayload) => {
  try {
    await addEntry(payload)
    showAddModal.value = false
    if (payload.slot_date === slotDate.value) {
      loadEntries()
    }
  } catch {
    // Error handled in composable
  }
}

const handleRemove = async (id: string) => {
  if (confirm('Êtes-vous sûr de vouloir supprimer cette entrée ?')) {
    await removeEntry(id)
  }
}
</script>

<template>
  <div class="py-6 px-4 sm:px-6 lg:px-8">
    <div class="sm:flex sm:items-center">
      <div class="sm:flex-auto">
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">Liste d'attente</h1>
        <p class="mt-2 text-sm text-gray-700 dark:text-gray-400">
          Gérez vos clients en attente de libération d'un créneau.
        </p>
      </div>
      <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
        <button
          @click="showAddModal = true"
          type="button"
          class="inline-flex items-center justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:w-auto"
        >
          Ajouter manuellement
        </button>
      </div>
    </div>

    <div class="mt-8 flex flex-col gap-6">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
          <div
            class="flex flex-wrap items-center justify-between gap-4 bg-white dark:bg-gray-900 p-4 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm mb-6"
          >
            <div class="flex items-center gap-4">
              <div>
                <label
                  for="slot_date"
                  class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"
                  >Date du créneau</label
                >
                <input
                  v-model="slotDate"
                  type="date"
                  id="slot_date"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100 sm:text-sm"
                />
              </div>
            </div>
            <div class="flex gap-4">
              <div class="text-center">
                <span
                  class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"
                  >En attente</span
                >
                <span class="mt-1 block text-lg font-bold text-gray-900 dark:text-gray-100">{{
                  pendingCount
                }}</span>
              </div>
              <div class="text-center">
                <span
                  class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"
                  >Notifiés</span
                >
                <span class="mt-1 block text-lg font-bold text-yellow-600 dark:text-yellow-400">{{
                  notifiedCount
                }}</span>
              </div>
            </div>
          </div>

          <div v-if="loading && !entries.length" class="flex justify-center py-12">
            <LoadingSpinner />
          </div>

          <ErrorMessage v-else-if="error" title="Erreur" :message="error" />

          <div
            v-else
            class="bg-white dark:bg-gray-900 shadow-sm border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden"
          >
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
              <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                  <th
                    scope="col"
                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"
                  >
                    Client
                  </th>
                  <th
                    scope="col"
                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"
                  >
                    Téléphone
                  </th>
                  <th
                    scope="col"
                    class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"
                  >
                    Couverts
                  </th>
                  <th
                    scope="col"
                    class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"
                  >
                    Statut
                  </th>
                  <th
                    scope="col"
                    class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"
                  >
                    Heure
                  </th>
                  <th scope="col" class="relative px-6 py-3">
                    <span class="sr-only">Actions</span>
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                <WaitlistEntryRow
                  v-for="entry in entries"
                  :key="entry.id"
                  :entry="entry"
                  @remove="handleRemove"
                  @notify="notify"
                />
                <tr v-if="!entries.length">
                  <td
                    colspan="6"
                    class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400"
                  >
                    Aucun client en attente pour cette date.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="lg:col-span-1">
          <WaitlistSettingsCard />
        </div>
      </div>
    </div>

    <AddWaitlistEntryModal
      :show="showAddModal"
      :loading="loading"
      @close="showAddModal = false"
      @submit="handleAddEntry"
    />
  </div>
</template>
