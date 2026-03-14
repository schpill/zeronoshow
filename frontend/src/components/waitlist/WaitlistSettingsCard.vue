<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useWaitlist } from '@/composables/useWaitlist'
import { useToast } from '@/composables/useToast'

const { settings, fetchSettings, updateSettings, regenerateLink } = useWaitlist()
const { success } = useToast()

const copying = ref(false)

onMounted(fetchSettings)

const handleToggle = () => {
  if (settings.value) {
    updateSettings({ waitlist_enabled: !settings.value.waitlist_enabled })
  }
}

const handleWindowChange = (event: Event) => {
  const value = parseInt((event.target as HTMLInputElement).value)
  updateSettings({ waitlist_notification_window_minutes: value })
}

const copyToClipboard = () => {
  if (settings.value?.public_registration_url) {
    navigator.clipboard.writeText(settings.value.public_registration_url)
    copying.value = true
    success('Lien copié !')
    setTimeout(() => {
      copying.value = false
    }, 2000)
  }
}

const handleRegenerate = () => {
  if (confirm('L\'ancien lien ne fonctionnera plus. Voulez-vous vraiment régénérer le lien public ?')) {
    regenerateLink()
  }
}
</script>

<template>
  <div v-if="settings" class="bg-white dark:bg-gray-900 shadow-sm border border-gray-200 dark:border-gray-700 rounded-xl p-6">
    <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-6">Paramètres de la liste d'attente</h2>

    <div class="space-y-8">
      <!-- Enable Toggle -->
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm font-semibold text-gray-900 dark:text-white">Activer la liste d'attente</p>
          <p class="text-xs text-gray-500 dark:text-gray-400">Autorise les clients à s'inscrire et active les notifications automatiques.</p>
        </div>
        <button
          @click="handleToggle"
          class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2"
          :class="settings.waitlist_enabled ? 'bg-indigo-600' : 'bg-gray-200 dark:bg-gray-700'"
        >
          <span
            aria-hidden="true"
            class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
            :class="settings.waitlist_enabled ? 'translate-x-5' : 'translate-x-0'"
          />
        </button>
      </div>

      <!-- Window Slider -->
      <div>
        <div class="flex justify-between items-center mb-2">
          <label class="text-sm font-semibold text-gray-900 dark:text-white">Délai de confirmation</label>
          <span class="text-sm font-bold text-indigo-600 dark:text-indigo-400">{{ settings.waitlist_notification_window_minutes }} minutes</span>
        </div>
        <input
          type="range"
          min="5"
          max="60"
          step="5"
          :value="settings.waitlist_notification_window_minutes"
          @change="handleWindowChange"
          class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer accent-indigo-600"
        />
        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Temps accordé au client pour confirmer son créneau avant de passer au suivant.</p>
      </div>

      <!-- Public Link -->
      <div v-if="settings.waitlist_enabled">
        <label class="text-sm font-semibold text-gray-900 dark:text-white block mb-2">Lien d'inscription public</label>
        <div class="flex gap-2">
          <input
            type="text"
            readonly
            :value="settings.public_registration_url || 'Lien non généré'"
            class="block w-full rounded-md border-gray-300 bg-gray-50 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300 shadow-sm sm:text-sm"
          />
          <button
            @click="copyToClipboard"
            class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700 dark:hover:bg-gray-700"
          >
            {{ copying ? 'Copié !' : 'Copier' }}
          </button>
        </div>
        <div class="mt-2 flex justify-end">
          <button
            @click="handleRegenerate"
            class="text-xs text-gray-500 hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400 underline underline-offset-2"
          >
            Régénérer le lien
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
