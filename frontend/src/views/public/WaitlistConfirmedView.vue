<script setup lang="ts">
import { useRoute } from 'vue-router'
import { computed } from 'vue'

const route = useRoute()
const name = computed(() => route.query.name as string || 'Client')
const slot = computed(() => {
  const s = route.query.slot as string
  if (!s) return 'le créneau choisi'
  const date = new Date(s)
  return `le ${date.toLocaleDateString('fr-FR')} à ${date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })}`
})
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-950 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8 text-center">
      <div class="bg-white dark:bg-gray-900 p-8 rounded-2xl shadow-xl border border-green-100 dark:border-green-900/30">
        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 dark:bg-green-900/30">
          <svg class="h-10 w-10 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
        </div>
        <h2 class="mt-6 text-3xl font-extrabold text-gray-900 dark:text-white">Réservation confirmée !</h2>
        <p class="mt-4 text-lg text-gray-600 dark:text-gray-400">
          {{ name }}, votre table est réservée pour {{ slot }}.
        </p>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-500">
          À très bientôt !
        </p>
      </div>
    </div>
  </div>
</template>
