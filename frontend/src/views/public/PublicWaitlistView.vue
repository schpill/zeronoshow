<script setup lang="ts">
import { ref, onMounted, reactive } from 'vue'
import { useRoute } from 'vue-router'
import { useWaitlist } from '@/composables/useWaitlist'
import LoadingSpinner from '@/components/LoadingSpinner.vue'

const route = useRoute()
const token = route.params.token as string

const { getPublicWaitlistInfo, joinWaitlistPublic } = useWaitlist()

const businessName = ref('')
const slots = ref<{ date: string; times: string[] }[]>([])
const loading = ref(true)
const error = ref<string | null>(null)
const success = ref(false)
const submitting = ref(false)

const form = reactive({
  slot_date: '',
  slot_time: '',
  client_name: '',
  client_phone: '+33',
  party_size: 2,
})

const fetchInfo = async () => {
  try {
    const info = await getPublicWaitlistInfo(token)
    businessName.value = info.business_name
    slots.value = info.slots_available
    const firstSlot = slots.value[0]
    if (firstSlot && firstSlot.times.length > 0) {
      form.slot_date = firstSlot.date
      form.slot_time = firstSlot.times[0] || ''
    }
  } catch {
    error.value = 'Lien invalide ou expiré.'
  } finally {
    loading.value = false
  }
}

onMounted(fetchInfo)

const handleSubmit = async () => {
  submitting.value = true
  try {
    await joinWaitlistPublic(token, form)
    success.value = true
  } catch {
    // Error handled by composable or shown here
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-950 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md mx-auto">
      <div v-if="loading" class="flex justify-center py-12">
        <LoadingSpinner />
      </div>

      <div
        v-else-if="error"
        class="bg-white dark:bg-gray-900 p-8 rounded-2xl shadow-xl text-center border border-red-100 dark:border-red-900/30"
      >
        <svg
          class="mx-auto h-12 w-12 text-red-500"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
          />
        </svg>
        <h2 class="mt-4 text-xl font-bold text-gray-900 dark:text-white">{{ error }}</h2>
      </div>

      <div
        v-else-if="success"
        class="bg-white dark:bg-gray-900 p-8 rounded-2xl shadow-xl text-center border border-green-100 dark:border-green-900/30"
      >
        <svg
          class="mx-auto h-12 w-12 text-green-500"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
          />
        </svg>
        <h2 class="mt-4 text-xl font-bold text-gray-900 dark:text-white">
          Vous êtes sur la liste !
        </h2>
        <p class="mt-2 text-gray-600 dark:text-gray-400">
          Nous vous contacterons par SMS si une place se libère pour votre créneau.
        </p>
      </div>

      <div
        v-else
        class="bg-white dark:bg-gray-900 p-8 rounded-2xl shadow-xl border border-gray-100 dark:border-gray-800"
      >
        <div class="text-center mb-8">
          <h2 class="text-2xl font-extrabold text-gray-900 dark:text-white">Liste d'attente</h2>
          <p class="mt-2 text-gray-600 dark:text-gray-400">{{ businessName }}</p>
        </div>

        <form @submit.prevent="handleSubmit" class="space-y-6">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300"
              >Date souhaitée</label
            >
            <select
              v-model="form.slot_date"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100 sm:text-sm"
            >
              <option v-for="day in slots" :key="day.date" :value="day.date">
                {{
                  new Date(day.date).toLocaleDateString('fr-FR', {
                    weekday: 'long',
                    day: 'numeric',
                    month: 'long',
                  })
                }}
              </option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300"
              >Heure approximative</label
            >
            <select
              v-model="form.slot_time"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100 sm:text-sm"
            >
              <option
                v-for="time in slots.find((s) => s.date === form.slot_date)?.times"
                :key="time"
                :value="time"
              >
                {{ time }}
              </option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300"
              >Nom complet</label
            >
            <input
              v-model="form.client_name"
              type="text"
              required
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100 sm:text-sm"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300"
              >Téléphone</label
            >
            <input
              v-model="form.client_phone"
              type="tel"
              required
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100 sm:text-sm"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300"
              >Nombre de personnes</label
            >
            <input
              v-model="form.party_size"
              type="number"
              min="1"
              max="20"
              required
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100 sm:text-sm"
            />
          </div>

          <button
            type="submit"
            :disabled="submitting"
            class="w-full flex justify-center py-3 px-4 border border-transparent rounded-xl shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 transition-all"
          >
            {{ submitting ? 'Envoi...' : "S'inscrire sur la liste" }}
          </button>
        </form>
      </div>
    </div>
  </div>
</template>
